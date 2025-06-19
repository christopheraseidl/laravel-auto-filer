<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\BatchUpdate as BatchUpdateContract;

final class BatchUpdate extends ModelAware implements BatchUpdateContract
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }
}
