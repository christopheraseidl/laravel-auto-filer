<?php

namespace christopheraseidl\HasUploads\Payloads;

final class DeleteUploads extends ModelAware
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}
