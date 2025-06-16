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
        $this->validateMaxAttempts($maxAttempts);
        $this->checkCircuitBreaker($disk, $oldPath, $newDir);

        $newPath = $this->buildNewPath($newDir, $oldPath);
        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
            try {
                return $this->performMove($disk, $oldPath, $newPath);
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                Log::warning('Move attempt failed.', [
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                ]);

                $this->handleMoveFailure($disk, $attempts, $maxAttempts);
            }
        }

        if ($attempts === $maxAttempts) {
            $this->breaker->recordFailure();
        }

        $this->logMoveFailure($disk, $oldPath, $newDir, $maxAttempts, $lastException);

        throw new \Exception("Failed to move file after {$maxAttempts} attempts.");
    }

    public function attemptUndoMove(string $disk, int $maxAttempts = 3, bool $throwOnFailure = true): array
    {
        $this->validateMaxAttempts($maxAttempts);

        $results = $this->processUndoOperations($disk, $maxAttempts);

        if (empty($results['failures'])) {
            $this->handleSuccessfulUndo();
        } else {
            $this->handleFailedUndo($disk, $results, $throwOnFailure);
        }

        return $results['successes'];
    }

    protected function validateMaxAttempts(int $maxAttempts): void
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }
    }

    protected function checkCircuitBreaker(string $disk, string $oldPath, string $newDir): void
    {
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
    }

    protected function buildNewPath(string $newDir, string $oldPath): string
    {
        return "{$newDir}/".pathinfo($oldPath, PATHINFO_BASENAME);
    }

    protected function performMove(string $disk, string $oldPath, string $newPath): string
    {
        if (! Storage::disk($disk)->copy($oldPath, $newPath)) {
            $this->breaker->recordFailure();
            throw new \Exception('Failed to copy file.');
        }

        if (! $this->exists($disk, $newPath)) {
            $this->breaker->recordFailure();
            throw new \Exception('Copy succeeded but file not found at destination.');
        }

        Storage::disk($disk)->delete($oldPath);
        $commit = $this->commitMovedFile($oldPath, $newPath);
        $this->breaker->recordSuccess();

        return $commit;
    }

    protected function handleMoveFailure(string $disk, int $attempts, int $maxAttempts): void
    {
        if ($attempts === $maxAttempts) {
            if (! empty($this->movedFiles)) {
                try {
                    $this->attemptUndoMove($disk, $maxAttempts, false);
                } catch (\Exception $e) {
                    Log::error('Unexpected exception during undo after move failure.', [
                        'error' => $e->getMessage(),
                        'disk' => $disk,
                    ]);
                }
            }
        } elseif ($this->breaker->canAttempt()) {
            $this->waitBeforeRetry();
        }
    }

    protected function waitBeforeRetry(): void
    {
        sleep(1);
    }

    protected function logMoveFailure(string $disk, string $oldPath, string $newDir, int $maxAttempts, ?\Exception $lastException): void
    {
        Log::error("Failed to move file after {$maxAttempts} attempts.", [
            'disk' => $disk,
            'oldPath' => $oldPath,
            'newDir' => $newDir,
            'maxAttempts' => $maxAttempts,
            'lastError' => $lastException?->getMessage(),
        ]);
    }

    protected function processUndoOperations(string $disk, int $maxAttempts): array
    {
        if (! $this->breaker->canAttempt()) {
            Log::warning('Undo operations blocked by circuit breaker.', [
                'disk' => $disk,
                'pending_undos' => count($this->movedFiles),
            ]);

            return ['failures' => $this->movedFiles, 'successes' => []];
        }

        $failures = [];
        $successes = [];

        foreach ($this->movedFiles as $oldPath => $newPath) {
            $result = $this->attemptSingleUndo($disk, $oldPath, $newPath, $maxAttempts);

            if ($result) {
                $successes[$oldPath] = $newPath;
            } else {
                $failures[$oldPath] = $newPath;
            }
        }

        return ['failures' => $failures, 'successes' => $successes];
    }

    protected function attemptSingleUndo(string $disk, string $oldPath, string $newPath, int $maxAttempts): bool
    {
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
            try {
                $this->undoSingleFile($disk, $oldPath, $newPath);
                $this->breaker->recordSuccess();

                return true;
            } catch (\Exception $e) {
                $attempts++;

                Log::warning('Undo attempt failed.', [
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                ]);

                if ($attempts < $maxAttempts) {
                    $this->waitBeforeRetry();
                }
            }
        }

        return false;
    }

    protected function handleSuccessfulUndo(): void
    {
        $this->clearMovedFiles();
    }

    protected function handleFailedUndo(string $disk, array $results, bool $throwOnFailure = true): void
    {
        $this->breaker->recordFailure();

        Log::error('File move undo failure.', [
            'disk' => $disk,
            'failed' => $results['failures'],
            'succeeded' => $results['successes'],
        ]);

        $this->uncommitSuccessfulUndos($results['successes']);

        if ($throwOnFailure) {
            throw new \Exception(sprintf(
                'Failed to undo %d file move(s): %s',
                count($results['failures']),
                json_encode($results['failures'], JSON_PRETTY_PRINT)
            ));
        }
    }

    protected function uncommitSuccessfulUndos(array $successes): void
    {
        foreach ($successes as $oldPath => $newPath) {
            $this->uncommitMovedFile($oldPath);
        }
    }

    private function undoSingleFile(string $disk, string $oldPath, string $newPath): void
    {
        if (! $this->exists($disk, $newPath)) {
            return; // Nothing to undo
        }

        // Restore original if it doesn't exist
        if (! $this->exists($disk, $oldPath)) {
            Storage::disk($disk)->copy($newPath, $oldPath);
        }

        // Verify restoration before deleting
        if ($this->exists($disk, $oldPath)) {
            Storage::disk($disk)->delete($newPath);
        } else {
            throw new \Exception('Failed to restore file to original location.');
        }
    }

    private function exists(string $disk, $path): bool
    {
        return Storage::disk($disk)->exists($path) && Storage::disk($disk)->size($path) > 0;
    }

    private function commitMovedFile(string $oldPath, string $newPath): string
    {
        return $this->movedFiles[$oldPath] = $newPath;
    }

    private function uncommitMovedFile(string $oldPath): void
    {
        unset($this->movedFiles[$oldPath]);
    }

    private function clearMovedFiles(): void
    {
        $this->movedFiles = [];
    }
}
