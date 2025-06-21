<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\BatchUpdate as BatchUpdateContract;

class BatchUpdate extends ModelAware implements BatchUpdateContract
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }
}
