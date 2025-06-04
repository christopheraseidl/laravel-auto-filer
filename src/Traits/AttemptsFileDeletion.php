<?php

namespace christopheraseidl\HasUploads\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait AttemptsFileDeletion
{
    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }

        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                return Storage::disk($disk)->delete($path);
            } catch (\Exception $e) {
                $attempts++;
                $lastException = $e;
                if ($attempts !== $maxAttempts) {
                    sleep(1);
                }
            }
        }

        Log::error("Failed to delete file after {$maxAttempts} attempts.", [
            'disk' => $disk,
            'path' => $path,
            'lastError' => $lastException->getMessage(),
        ]);

        throw new \Exception("Failed to delete file after {$maxAttempts} attempts.");
    }
}
