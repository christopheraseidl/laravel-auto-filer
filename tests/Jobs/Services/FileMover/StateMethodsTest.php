<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use christopheraseidl\Reflect\Reflect;

/**
 * Tests FileMover state methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('can commit a single moved file', function () {
    $this->mover->shouldReceive('addToMovedFiles')
        ->once()
        ->with($this->oldPath, $this->newPath);

    $this->mover->commitMovedFile($this->oldPath, $this->newPath);
});

it('adds a moved file to the movedFiles array', function () {
    $result = $this->mover->addToMovedFiles($this->oldPath, $this->newPath);
    $movedFilesArray = Reflect::on($this->mover)->movedFiles;

    expect($result)->toBe($this->newPath);
    expect($movedFilesArray[$this->oldPath])->toBe($this->newPath);
});

it('can uncommit a single file', function () {
    $movedFiles = [
        $this->oldPath => $this->newPath,
    ];

    $this->mover->shouldReceive('getMovedFiles')
        ->once()
        ->andReturn($movedFiles);

    $this->mover = Reflect::on($this->mover);
    $this->mover->movedFiles = $movedFiles;

    $this->mover->uncommitMovedFile($this->oldPath);

    $isSet = isset($this->mover->movedFiles[$this->oldPath]);

    expect($isSet)->toBeFalse();
});

it('can uncommit successful undos', function () {
    $successes = [
        $this->oldPath => $this->newPath,
        'old/doc.pdf' => 'new/doc.pdf',
        'old/readme.md' => 'new/readme.md',
    ];

    $this->mover->shouldReceive('uncommitMovedFile')
        ->times(count($successes));

    $this->mover->uncommitSuccessfulUndos($successes);
});

it('can clear moved files', function () {
    $movedFiles = [
        $this->oldPath => $this->newPath,
        'old/doc.pdf' => 'new/doc.pdf',
        'old/readme.md' => 'new/readme.md',
    ];

    $this->mover = Reflect::on($this->mover);
    $this->mover->movedFiles = $movedFiles;

    $this->mover->clearMovedFiles();

    $result = $this->mover->movedFiles;

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

it('can get moved files', function () {
    $movedFiles = [
        $this->oldPath => $this->newPath,
        'old/doc.pdf' => 'new/doc.pdf',
        'old/readme.md' => 'new/readme.md',
    ];

    $this->mover = Reflect::on($this->mover);
    $this->mover->movedFiles = $movedFiles;

    $result = $this->mover->getMovedFiles();

    expect($result)->toBeArray()
        ->and($result)->toBe($movedFiles);
});
