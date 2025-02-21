<?php

namespace christopheraseidl\HasUploads\Payloads;

final class BatchUpdate extends ModelAware
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }
}
