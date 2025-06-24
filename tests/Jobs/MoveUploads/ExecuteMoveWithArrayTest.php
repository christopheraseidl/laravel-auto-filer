<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\MoveUploads;

/**
 * Tests the MoveUploads executeMove method with array (multiple file) attributes.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
 */
beforeEach(function () {
    $this->files = [
        'file1.text',
        'file2.pdf',
        'file3.doc',
        'file3.docx',
    ];

    $this->model->array = $this->files;
    $this->model->saveQuietly();

    $this->newDir = 'test_models/1/images';
});

it('moves an array of files to the new path and updates the model with the new location', function () {
    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('array');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($this->files);

    $movedFiles = array_map(fn ($file) => $this->newDir.'/'.basename($file), $this->files);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with($this->files)
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('arrayMerge')
        ->once()
        ->withArgs(function ($unmoved, $moved) use ($movedFiles) {
            return empty($unmoved) && $moved === $movedFiles;
        })
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->with($this->model, 'array')
        ->andReturn($movedFiles);

    $this->mover->executeMove();
});

it('respects custom upload path for array from type parameter', function () {
    $movedFiles = array_map(fn ($file) => $this->newDir.'/'.basename($file), $this->files);

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('array');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($this->files);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with($this->files)
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('arrayMerge')
        ->once()
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->andReturn($movedFiles);

    $this->mover->executeMove();

    foreach ($movedFiles as $file) {
        expect($file)->toStartWith($this->newDir);
    }
});

it('handles null array attribute gracefully', function () {
    $this->model->array = null;
    $this->model->saveQuietly();

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('array');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($this->files);

    $movedFiles = array_map(fn ($file) => $this->newDir.'/'.basename($file), $this->files);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with($this->files)
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('arrayMerge')
        ->once()
        ->withArgs(function ($unmoved, $moved) use ($movedFiles) {
            return empty($unmoved) && $moved === $movedFiles;
        })
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->with($this->model, 'array')
        ->andReturn($movedFiles);

    $this->mover->executeMove();
});

it('handles empty array attribute gracefully', function () {
    $this->model->array = [];
    $this->model->saveQuietly();

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('array');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($this->files);

    $movedFiles = array_map(fn ($file) => $this->newDir.'/'.basename($file), $this->files);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with($this->files)
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('arrayMerge')
        ->once()
        ->withArgs(function ($unmoved, $moved) use ($movedFiles) {
            return empty($unmoved) && $moved === $movedFiles;
        })
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->andReturn($movedFiles);

    $this->mover->executeMove();
});

it('makes no model changes when anything fails', function () {
    $originalValue = $this->model->fresh()->array;

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andThrow(new \Exception('Model resolution failed.'));

    expect(fn () => $this->mover->executeMove())
        ->toThrow(\Exception::class, 'Model resolution failed.');

    expect($this->model->fresh()->array)->toBe($originalValue);
});

it('combines unmoved and moved files correctly when only moving subset', function () {
    $filesToMove = ['file1.text', 'file2.pdf']; // Only moving 2 out of 4 files
    $unmovedFiles = ['file3.doc', 'file3.docx'];
    $movedFiles = [
        $this->newDir.'/file1.text',
        $this->newDir.'/file2.pdf',
    ];
    $combinedFiles = [...$unmovedFiles, ...$movedFiles];

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('array');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($filesToMove);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with($filesToMove)
        ->andReturn($movedFiles);

    $this->mover->shouldReceive('arrayMerge')
        ->once()
        ->withArgs(function (array $unmoved, array $moved) use ($unmovedFiles, $movedFiles) {
            return $unmoved === $unmovedFiles && $moved === $movedFiles;
        })
        ->andReturn($combinedFiles);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->with($this->model, 'array')
        ->andReturn($combinedFiles);

    $this->mover->executeMove();
});
