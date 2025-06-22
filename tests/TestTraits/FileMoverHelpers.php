<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

trait FileMoverHelpers
{
    public function shouldValidateMover(): void
    {
        $this->mover->shouldReceive('validateMaxAttempts')
            ->once()
            ->with(3);
        $this->mover->shouldReceive('checkCircuitBreaker')
            ->once()
            ->with('attempt move file', $this->disk, [
                'old_path' => $this->oldPath,
                'new_dir' => $this->newDir,
            ]);
    }
}
