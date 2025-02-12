<?php

namespace christopheraseidl\HasUploads\Payloads;

final class DeleteUploadsPayload extends ModelAwarePayload
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}
