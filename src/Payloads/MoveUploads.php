<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\MoveUploads as MoveUploadsContract;

/**
 * Provides model and file data to the MoveUploads job.
 */
class MoveUploads extends ModelAware implements MoveUploadsContract
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

    /**
     * Determine whether individual events should be broadcast.
     */
    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}
