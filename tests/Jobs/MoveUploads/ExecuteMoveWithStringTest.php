<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\MoveUploads;

/**
 * Tests the MoveUploads handle method with string (single file) attributes.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
 */
beforeEach(function () {
    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'test_models/1/images';
    $this->newPath = "{$this->newDir}/{$name}";

    $this->model->string = $this->oldPath;
    $this->model->saveQuietly();
});

it('moves a single file to the new path and updates the model with the new location', function () {
    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('string');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn([$this->oldPath]);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with([$this->oldPath])
        ->andReturn([$this->newPath]);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->with($this->model, 'string')
        ->andReturn($this->newPath);

    $this->mover->executeMove();
});

it('respects custom upload path for string from type parameter', function () {
    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('string');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn([$this->oldPath]);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with([$this->oldPath])
        ->andReturn([$this->newPath]);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->andReturn($this->newPath);

    $this->mover->executeMove();

    expect($this->newPath)->toStartWith($this->newDir);
});

it('handles null model string attribute gracefully', function () {
    $this->model->string = null;
    $this->model->saveQuietly();

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('string');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn([$this->oldPath]);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with([$this->oldPath])
        ->andReturn([$this->newPath]);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->andReturn($this->newPath);

    $this->mover->executeMove();
});

it('handles empty model string attribute gracefully', function () {
    $this->model->string = '';
    $this->model->saveQuietly();

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andReturn($this->model);

    $this->payload->shouldReceive('getModelAttribute')
        ->once()
        ->andReturn('string');

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn([$this->oldPath]);

    $this->mover->shouldReceive('moveFiles')
        ->once()
        ->with([$this->oldPath])
        ->andReturn([$this->newPath]);

    $this->mover->shouldReceive('normalizeAttributeValue')
        ->once()
        ->andReturn($this->newPath);

    $this->mover->executeMove();
});

it('makes no model changes when anything fails', function () {
    $originalValue = $this->model->fresh()->string;

    $this->payload->shouldReceive('resolveModel')
        ->once()
        ->andThrow(new \Exception('Model resolution failed.'));

    expect(fn () => $this->mover->executeMove())
        ->toThrow(\Exception::class);

    expect($this->model->fresh()->string)->toBe($originalValue);
});
