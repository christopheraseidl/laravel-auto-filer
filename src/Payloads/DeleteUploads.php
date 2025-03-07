<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;

final class DeleteUploads extends ModelAware implements DeleteUploadsContract
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}
