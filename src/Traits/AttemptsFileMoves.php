<?php

namespace christopheraseidl\HasUploads\Traits;

use Exception;
use Illuminate\Support\Facades\Storage;

trait AttemptsFileMoves
{
    public function attemptMove(string $disk, string $oldPath, string $newDir): string
    {
        $newPath = "{$newDir}/".pathinfo($oldPath, PATHINFO_BASENAME);
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            try {
                Storage::disk($disk)->move($oldPath, $newPath);

                return $newPath;
            } catch (Exception $e) {
                $attempts++;
                if ($attempts === $maxAttempts) {
                    throw $e;
                }
                sleep(1);
            }
        }

        throw new Exception("Failed to move file after {$maxAttempts} attempts.");
    }
}
