<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploadDirectory;

/**
 * Tests the DeleteUploadDirectory handle method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
it('runs the job inside the handleJob method', function () {
    $this->deleter->shouldReceive('handleJob')->once();

    $this->deleter->handle();
});
