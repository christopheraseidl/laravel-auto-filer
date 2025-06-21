<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;

class DeleteUploads extends ModelAware implements DeleteUploadsContract
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}
