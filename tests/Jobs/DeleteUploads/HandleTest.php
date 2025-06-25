<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploads;

/**
 * Tests the DeleteUploads handle method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
it('runs the job inside the handleJob method', function () {
    $this->deleter->shouldReceive('handleJob')
        ->once()
        ->withArgs(function ($closure) {
            expect($closure)->toBeCallable();

            $this->deleter->shouldReceive('executeDeletion')->once();

            // Execute the closure to verify it calls executeCleaning
            $closure();

            return true;
        });

    $this->deleter->handle();
});
