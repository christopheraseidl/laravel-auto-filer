<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsContract;

/**
 * Provides model and file data to the MoveUploads job.
 */
final class MoveUploads extends ModelAware implements MoveUploadsContract
{
    /**
     * Generate unique key including destination directory hash.
     */
    public function getKey(): string
    {
        $baseKey = parent::getKey();

        $newFilePathsIdentifier = md5(serialize($this->getNewDir()));

        return "{$baseKey}_{$newFilePathsIdentifier}";
    }

    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}
