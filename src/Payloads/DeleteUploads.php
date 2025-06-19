<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;

final class DeleteUploads extends ModelAware implements DeleteUploadsContract
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}
