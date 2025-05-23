<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\BatchUpdate as BatchUpdateContract;

final class BatchUpdate extends ModelAware implements BatchUpdateContract
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }
}
