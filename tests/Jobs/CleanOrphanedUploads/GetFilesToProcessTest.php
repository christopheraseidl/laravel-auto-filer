<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads;
use Illuminate\Support\Facades\Storage;

it('gets the files to process', function () {
    $payload = $this->partialMock(CleanOrphanedUploads::class);

    $dir = 'path/to';

    $files = [
        "{$dir}/image.png",
        "{$dir}/document.txt",
        "{$dir}/log.log",
    ];

    $files = array_map(fn ($file) => Storage::disk($this->disk)->put($file, 'content'), $files);

    $result = $payload->getFilesToProcess($this->disk, $dir);

    expect($result)->toEqual($files);
});
