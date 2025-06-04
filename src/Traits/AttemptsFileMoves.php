<?php

namespace christopheraseidl\HasUploads\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait AttemptsFileMoves
{
    protected array $movedFiles = [];

    public function attemptMove(string $disk, string $oldPath, string $newDir, int $maxAttempts = 3): string
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }

        $newPath = "{$newDir}/".pathinfo($oldPath, PATHINFO_BASENAME);
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                if (Storage::disk($disk)->copy($oldPath, $newPath)) {
                    if ($this->exists($disk, $newPath)) {
                        Storage::disk($disk)->delete($oldPath);

                        return $this->commitMovedFile($oldPath, $newPath);
                    }
                }

                throw new \Exception('Copy succeeded but file not found at destination.');
            } catch (\Exception $e) {
                $attempts++;
                if ($attempts === $maxAttempts) {
                    if (! empty($this->movedFiles)) {
                        $this->attemptUndoMove($disk);
                    }
                } else {
                    sleep(1);
                }
            }
        }

        throw new \Exception("Failed to move file after {$maxAttempts} attempts.");
    }

    public function attemptUndoMove(string $disk, int $maxAttempts = 3): ?array
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }
        $failures = [];
        $successes = [];

        foreach ($this->movedFiles as $oldPath => $newPath) {
            $attempts = 0;

            while ($attempts < $maxAttempts) {
                try {
                    $this->undoSingleFile($disk, $oldPath, $newPath);
                    $successes[$oldPath] = $newPath;

                    break;
                } catch (\Exception $e) {
                    $attempts++;
                    if ($attempts === $maxAttempts) {
                        $failures[$oldPath] = $newPath;
                    } else {
                        sleep(1);
                    }
                }
            }
        }

        if (empty($failures)) {
            $this->clearMovedFiles();
        } else {
            Log::error('Partial file move undo failure', [
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

    public function clearMovedFiles(): void
    {
        $this->movedFiles = [];
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
}
