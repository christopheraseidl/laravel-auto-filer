<?php

namespace christopheraseidl\HasUploads\Payloads;

final class BatchUpdatePayload extends ModelAwarePayload
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }
}
