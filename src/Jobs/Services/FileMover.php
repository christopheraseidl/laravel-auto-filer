<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Contracts\FileMover as FileMoverContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileMover implements FileMoverContract
{
    public function __construct(
        protected CircuitBreaker $breaker,
        protected array $movedFiles = []
    ) {}

    public function attemptMove(string $disk, string $oldPath, string $newDir, int $maxAttempts = 3): string
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }

        if (! $this->breaker->canAttempt()) {
            Log::warning('File operation blocked by circuit breaker', [
                'operation' => 'move file',
                'disk' => $disk,
                'oldPath' => $oldPath,
                'newDir' => $newDir,
                'breaker_stats' => $this->breaker->getStats(),
            ]);

            throw new \Exception('File operations are currently unavailable due to repeated failures. Please try again later.');
        }

        $lastException = null;
        $newPath = "{$newDir}/".pathinfo($oldPath, PATHINFO_BASENAME);
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
            try {
                if (Storage::disk($disk)->copy($oldPath, $newPath)) {
                    if ($this->exists($disk, $newPath)) {
                        Storage::disk($disk)->delete($oldPath);
                        $commit = $this->commitMovedFile($oldPath, $newPath);
                        $this->breaker->recordSuccess();

                        return $commit;
                    }
                }

                $this->breaker->recordFailure();

                throw new \Exception('Copy succeeded but file not found at destination.');
            } catch (\Exception $e) {
                $attempts++;
                $lastException = $e;
                if ($attempts === $maxAttempts) {
                    if (! empty($this->movedFiles)) {
                        $this->attemptUndoMove($disk);
                    }
                } elseif ($this->breaker->canAttempt()) {
                    sleep(1);
                }
            }
        }

        $this->breaker->recordFailure();

        Log::error("Failed to move file after {$maxAttempts} attempts.", [
            'disk' => $disk,
            'oldPath' => $oldPath,
            'newDir' => $newDir,
            'maxAttempts' => $maxAttempts,
            'lastError' => $lastException->getMessage(),
        ]);

        throw new \Exception("Failed to move file after {$maxAttempts} attempts.");
    }

    public function attemptUndoMove(string $disk, int $maxAttempts = 3): array
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }

        $failures = [];
        $successes = [];

        foreach ($this->movedFiles as $oldPath => $newPath) {
            $attempts = 0;

            while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
                try {
                    $this->undoSingleFile($disk, $oldPath, $newPath);
                    $successes[$oldPath] = $newPath;
                    $this->breaker->recordSuccess();

                    break;
                } catch (\Exception $e) {
                    $attempts++;
                    if ($attempts === $maxAttempts) {
                        $failures[$oldPath] = $newPath;
                    } elseif ($this->breaker->canAttempt()) {
                        sleep(1);
                    }
                }
            }
        }

        if (empty($failures)) {
            $this->clearMovedFiles();
        } else {
            $this->breaker->recordFailure();

            Log::error('File move undo failure.', [
                'disk' => $disk,
                'failed' => $failures,
                'succeeded' => array_diff_key($this->movedFiles, $failures),
            ]);

            foreach ($this->movedFiles as $oldPath => $newPath) {
                if (! isset($failures[$oldPath])) {
                    $this->uncommitMovedFile($oldPath);
                }
            }

            throw new \Exception(sprintf(
                'Failed to undo %d file move(s): %s',
                count($failures),
                json_encode($failures, JSON_PRETTY_PRINT)
            ));
        }

        return $successes;
    }

    private function undoSingleFile(string $disk, string $oldPath, string $newPath): string
    {
        if ($this->exists($disk, $newPath)) {
            if (! $this->exists($disk, $oldPath)) {
                Storage::disk($disk)->copy($newPath, $oldPath);
            }

            if ($this->exists($disk, $oldPath)) {
                Storage::disk($disk)->delete($newPath);
            }
        }

        return $oldPath;
    }

    private function exists(string $disk, $path): bool
    {
        return Storage::disk($disk)->exists($path) && Storage::disk($disk)->size($path) > 0;
    }

    private function commitMovedFile(string $oldPath, string $newPath): string
    {
        return $this->movedFiles[$oldPath] = $newPath;
    }

    private function uncommitMovedFile(string $oldPath): string
    {
        unset($this->movedFiles[$oldPath]);

        return $oldPath;
    }

    private function clearMovedFiles(): void
    {
        $this->movedFiles = [];
    }
}
