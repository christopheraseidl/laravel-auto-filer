<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsContract;

final class MoveUploads extends ModelAware implements MoveUploadsContract
{
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
