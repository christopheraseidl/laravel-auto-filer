<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploads;

use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use Illuminate\Support\Arr;

/**
 * Tests that the DeleteUploads job generates unique identifiers using the
 * expected format of model_id_operation_files.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
beforeEach(function () {
    $this->model->string = 'my_path/image.jpg';
    $this->model->saveQuietly();
    $this->model->refresh();
});

it('returns the correct unique ID for a string', function () {
    $job = new DeleteUploads(
        $this->model,
        $this->model->string,
        'images'
    );

    $uniqueFilesHash = md5(serialize(Arr::wrap($this->model->string)));

    $uniqueId = $job->uniqueId();

    expect($uniqueId)->toBe("test_model_1_delete_{$uniqueFilesHash}");
});
