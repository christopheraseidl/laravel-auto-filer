<?php

namespace christopheraseidl\HasUploads\Payloads;

final class MoveUploads extends ModelAware
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
