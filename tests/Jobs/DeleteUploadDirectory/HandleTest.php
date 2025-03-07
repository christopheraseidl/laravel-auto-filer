<?php

use Illuminate\Support\Facades\Storage;

it('deletes the correct directory', function () {
    $dir = 'test_models/1';
    $file = $dir . '/my_file.txt';
    Storage::disk($this->disk)->put($file, 'content');

    $this->model->string = $file;
    $this->model->saveQuietly();

    expect(Storage::disk($this->disk)->exists($file))
        ->toBeTrue()
        ->and(Storage::disk($this->disk)->exists($dir))
        ->toBeTrue();

    $this->job->handle();

    expect(Storage::disk($this->disk)->exists($file))
        ->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($dir))
        ->toBeFalse();
});
