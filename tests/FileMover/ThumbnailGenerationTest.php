<?php

namespace christopheraseidl\AutoFiler\Tests\FileMover;

use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Services\FileMoverService;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->circuitBreaker = $this->mock(CircuitBreakerContract::class);
    $this->thumbnailGenerator = $this->mock(GenerateThumbnail::class);
    $this->service = new FileMoverService($this->circuitBreaker, $this->thumbnailGenerator);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
    config()->set('auto-filer.thumbnails.enabled', true);
    config()->set('auto-filer.thumbnails.width', 150);
    config()->set('auto-filer.thumbnails.height', 150);
    config()->set('auto-filer.thumbnails.quality', 80);
    config()->set('auto-filer.thumbnails.suffix', '_thumb');
    config()->set('auto-filer.retry_wait_seconds', 0); // to speed up tests
});

it('generates thumbnails for image files when enabled', function () {
    Storage::disk('public')->put('source/image.jpg', 'image content');

    $this->thumbnailGenerator->shouldReceive('__invoke')
        ->once()
        ->with('destination/image.jpg')
        ->andReturn(['success' => true, 'path' => 'destination/image_thumb.jpg']);

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->move('source/image.jpg', 'destination/image.jpg');

    expect($result)->toBe('destination/image.jpg');
});

it('tracks thumbnails for rollback', function () {
    Storage::disk('public')->put('source/image.jpg', 'image content');

    $this->thumbnailGenerator->shouldReceive('__invoke')
        ->once()
        ->with('destination/image.jpg')
        ->andReturn(['success' => true, 'path' => 'destination/image_thumb.jpg']);

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $this->service->move('source/image.jpg', 'destination/image.jpg');

    // Access private property using reflection for testing
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);

    expect($movedFiles->getValue($this->service))
        ->toHaveKey('source/image.jpg', 'destination/image.jpg')
        ->toHaveKey('destination/image.jpg_thumb', 'destination/image_thumb.jpg');
});

it('skips thumbnail generation when disabled', function () {
    config()->set('auto-filer.thumbnails.enabled', false);

    Storage::disk('public')->put('source/image.jpg', 'image content');

    $this->thumbnailGenerator->shouldNotReceive('__invoke');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->move('source/image.jpg', 'destination/image.jpg');

    expect($result)->toBe('destination/image.jpg');
});

it('skips thumbnail generation for non-image files', function () {
    Storage::disk('public')->put('source/document.pdf', 'pdf content');

    $this->thumbnailGenerator->shouldNotReceive('__invoke');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->move('source/document.pdf', 'destination/document.pdf');

    expect($result)->toBe('destination/document.pdf');
});

it('handles thumbnail generation failures gracefully', function () {
    Storage::disk('public')->put('source/image.jpg', 'image content');

    $this->thumbnailGenerator->shouldReceive('__invoke')
        ->once()
        ->with('destination/image.jpg')
        ->andReturn(['success' => false, 'error' => 'Thumbnail generation failed']);

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->once();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->move('source/image.jpg', 'destination/image.jpg');

    expect($result)->toBe('destination/image.jpg');

    // Ensure thumbnail is not tracked when generation fails
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);

    expect($movedFiles->getValue($this->service))
        ->toHaveKey('source/image.jpg', 'destination/image.jpg')
        ->not->toHaveKey('destination/image.jpg_thumb');
});

it('identifies image files correctly for thumbnail generation', function () {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('shouldGenerateThumbnail');
    $method->setAccessible(true);

    Storage::shouldReceive('disk->mimeType')
        ->with('test.jpg')
        ->andReturn('image/jpeg');

    $result = $method->invoke($this->service, 'test.jpg');

    expect($result)->toBeTrue();
});

it('does not generate thumbnails for non-image mime types', function () {
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('shouldGenerateThumbnail');
    $method->setAccessible(true);

    Storage::shouldReceive('disk->mimeType')
        ->with('test.pdf')
        ->andReturn('application/pdf');

    $result = $method->invoke($this->service, 'test.pdf');

    expect($result)->toBeFalse();
});
