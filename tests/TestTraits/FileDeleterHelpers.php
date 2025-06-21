<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

trait FileDeleterHelpers
{
    public function shouldValidateDeleter(): void
    {
        $this->deleter->shouldReceive('validateMaxAttempts')
            ->once()
            ->with(3);
        $this->deleter->shouldReceive('checkCircuitBreaker')
            ->once()
            ->with('attempt delete file', $this->disk, [
                'path' => $this->path,
            ]);
    }
}
