<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Contracts\FileDeleter as FileDeleterContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileDeleter implements FileDeleterContract
{
    public function __construct(
        protected CircuitBreaker $breaker
    ) {}

    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }

        if (! $this->breaker->canAttempt()) {
            Log::warning('File operation blocked by circuit breaker', [
                'operation' => 'delete file',
                'disk' => $disk,
                'path' => $path,
                'breaker_stats' => $this->breaker->getStats(),
            ]);

            throw new \Exception('File operations are currently unavailable due to repeated failures. Please try again later.');
        }

        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
            try {
                $result = Storage::disk($disk)->delete($path);

                if ($result) {
                    $this->breaker->recordSuccess();

                    return true;
                } else {
                    $this->breaker->recordFailure();

                    throw new \Exception('Deletion operation returned false.');
                }
            } catch (\Exception $e) {
                $attempts++;
                $lastException = $e;

                Log::warning("File delete attempt {$attempts} failed", [
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts,
                    'max_attempts' => $maxAttempts,
                ]);

                if ($attempts !== $maxAttempts && $this->breaker->canAttempt()) {
                    sleep(1);
                }
            }
        }

        $this->breaker->recordFailure();

        Log::error("Failed to delete file after {$attempts} attempts.", [
            'disk' => $disk,
            'path' => $path,
            'lastError' => $lastException->getMessage(),
        ]);

        throw new \Exception("Failed to delete file after {$attempts} attempts.");
    }
}
