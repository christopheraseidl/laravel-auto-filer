<?php

namespace christopheraseidl\AutoFiler\Tests\Jobs;

use christopheraseidl\AutoFiler\Contracts\FileDeleter;
use christopheraseidl\AutoFiler\Contracts\FileMover;
use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use christopheraseidl\AutoFiler\Events\ProcessingComplete;
use christopheraseidl\AutoFiler\Events\ProcessingFailure;
use christopheraseidl\AutoFiler\Jobs\ProcessFileOperations;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModel;
use christopheraseidl\AutoFiler\ValueObjects\ChangeManifest;
use christopheraseidl\AutoFiler\ValueObjects\FileOperation;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->mover = $this->mock(FileMover::class);
    $this->deleter = $this->mock(FileDeleter::class);
    $this->scanner = $this->mock(RichTextScanner::class);

    Event::fake();
    Log::spy();
});

it('processes move operations and updates model attributes', function () {
    $model = TestModel::create(['avatar' => 'temp/old-file.jpg']);

    $operation = FileOperation::move(
        from: 'temp/old-file.jpg',
        to: 'images/new-file.jpg',
        model: $model,
        attribute: 'avatar'
    );

    $manifest = new ChangeManifest(collect([$operation]));

    $this->mover->shouldReceive('move')
        ->once()
        ->with('temp/old-file.jpg', 'images/new-file.jpg');

    $job = new ProcessFileOperations($manifest);
    $job->handle($this->mover, $this->deleter, $this->scanner);

    $model->refresh();
    expect($model->avatar)->toBe('images/new-file.jpg');

    Event::assertDispatched(ProcessingComplete::class);
});

it('processes move operations with array attributes', function () {
    $model = TestModel::create(['documents' => ['temp/doc1.pdf', 'temp/doc2.pdf']]);

    $operation = FileOperation::move(
        from: 'temp/doc1.pdf',
        to: 'files/doc1.pdf',
        model: $model,
        attribute: 'documents'
    );

    $manifest = new ChangeManifest(collect([$operation]));

    $this->mover->shouldReceive('move')
        ->once()
        ->with('temp/doc1.pdf', 'files/doc1.pdf');

    $job = new ProcessFileOperations($manifest);
    $job->handle($this->mover, $this->deleter, $this->scanner);

    $model->refresh();
    expect($model->documents)->toBe(['files/doc1.pdf', 'temp/doc2.pdf']);
});

it('processes rich text move operations', function () {
    $model = TestModel::create(['description' => 'Content with <img src="temp/image.jpg">']);

    $operation = FileOperation::moveRichText(
        from: 'temp/image.jpg',
        to: 'images/image.jpg',
        model: $model,
        attribute: 'description'
    );

    $manifest = new ChangeManifest(collect([$operation]));

    $this->mover->shouldReceive('move')
        ->once()
        ->with('temp/image.jpg', 'images/image.jpg');

    $this->scanner->shouldReceive('updatePaths')
        ->once()
        ->with('Content with <img src="temp/image.jpg">', ['temp/image.jpg' => 'images/image.jpg'])
        ->andReturn('Content with <img src="images/image.jpg">');

    $job = new ProcessFileOperations($manifest);
    $job->handle($this->mover, $this->deleter, $this->scanner);

    $model->refresh();
    expect($model->description)->toBe('Content with <img src="images/image.jpg">');
});

it('processes delete operations', function () {
    $operation = FileOperation::delete('temp/delete-me.jpg');

    $manifest = new ChangeManifest(collect([$operation]));

    $this->deleter->shouldReceive('delete')
        ->once()
        ->with('temp/delete-me.jpg');

    $job = new ProcessFileOperations($manifest);
    $job->handle($this->mover, $this->deleter, $this->scanner);

    Event::assertDispatched(ProcessingComplete::class);
});

it('processes multiple operations', function () {
    $model = TestModel::create(['avatar' => 'temp/avatar.jpg']);

    $moveOperation = FileOperation::move(
        from: 'temp/avatar.jpg',
        to: 'images/avatar.jpg',
        model: $model,
        attribute: 'avatar'
    );

    $deleteOperation = FileOperation::delete('temp/old-file.jpg');

    $manifest = new ChangeManifest(collect([$moveOperation, $deleteOperation]));

    $this->mover->shouldReceive('move')->once();
    $this->deleter->shouldReceive('delete')->once();

    $job = new ProcessFileOperations($manifest);
    $job->handle($this->mover, $this->deleter, $this->scanner);
});

it('rolls back all operations when one fails', function () {
    $model = TestModel::create(['avatar' => 'temp/avatar.jpg']);

    $moveOperation = FileOperation::move(
        from: 'temp/avatar.jpg',
        to: 'images/avatar.jpg',
        model: $model,
        attribute: 'avatar'
    );

    $deleteOperation = FileOperation::delete('temp/old-file.jpg');

    $manifest = new ChangeManifest(collect([$moveOperation, $deleteOperation]));

    // First operation succeeds
    $this->mover->shouldReceive('move')->once();

    // Second operation fails
    $this->deleter->shouldReceive('delete')
        ->once()
        ->andThrow(new \Exception('Delete failed'));

    $job = new ProcessFileOperations($manifest);

    // Assert exception is thrown
    expect(fn () => $job->handle($this->mover, $this->deleter, $this->scanner))
        ->toThrow(\Exception::class);

    // Assert model was NOT updated (rollback occurred due to DB transaction)
    $model->refresh();
    expect($model->avatar)->toBe('temp/avatar.jpg'); // Original value
});

it('handles exceptions and dispatches failure event', function () {
    $operation = FileOperation::delete('temp/file.jpg');

    $manifest = new ChangeManifest(collect([$operation]));

    $exception = new \Exception('File operation failed');
    $this->deleter->shouldReceive('delete')->andThrow($exception);

    $job = new ProcessFileOperations($manifest);

    expect(fn () => $job->handle($this->mover, $this->deleter, $this->scanner))
        ->toThrow(\Exception::class, 'File operation failed');

    Event::assertDispatched(ProcessingFailure::class, function ($event) use ($exception) {
        return $event->e === $exception;
    });

    Log::shouldHaveReceived('error')
        ->with('Auto Filer: ProcessFileOperations job temporarily failed', ['error' => 'File operation failed']);
});

it('throws exception when model not found in move operation', function (string $move) {
    $operation = FileOperation::$move(
        from: 'temp/file.jpg',
        to: 'images/file.jpg',
        model: new TestModel,
        attribute: 'avatar'
    );

    $manifest = new ChangeManifest(collect([$operation]));

    $this->mover->shouldReceive('move')->once();

    $job = new ProcessFileOperations($manifest);

    expect(fn () => $job->handle($this->mover, $this->deleter, $this->scanner))
        ->toThrow(\RuntimeException::class, 'Model not found: '.TestModel::class.'#');
})->with([
    'regular move' => 'move',
    'rich text move' => 'moveRichText',
]);

it('throws an exception for invalid file operation type', function () {
    $operation = new class
    {
        public $type = 'invalid';
    };

    $manifest = new ChangeManifest(collect([$operation]));
    $job = new ProcessFileOperations($manifest);
    expect(fn () => $job->handle($this->mover, $this->deleter, $this->scanner))
        ->toThrow(\InvalidArgumentException::class, 'Unknown operation type: invalid');
});

it('generates unique job ID based on operations', function () {
    $model = TestModel::create();

    $operation1 = FileOperation::move(
        from: 'temp/a.jpg',
        to: 'images/a.jpg',
        model: $model,
        attribute: 'avatar'
    );

    $operation2 = FileOperation::delete('temp/b.jpg');

    $manifest = new ChangeManifest(collect([$operation1, $operation2]));
    $job = new ProcessFileOperations($manifest);

    $expectedSignature = collect(['move:temp/a.jpg:images/a.jpg', 'delete:temp/b.jpg:'])
        ->sort()
        ->join('|');

    $expectedId = ProcessFileOperations::class.'_'.hash('sha256', $expectedSignature);

    expect($job->uniqueId())->toBe($expectedId);
});

it('configures retry timeout to 5 minutes', function () {
    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);

    // Freeze time
    Carbon::setTestNow(now());

    expect($job->retryUntil())->toEqual(now()->addMinutes(5));
});

it('logs permanent failure', function () {
    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);
    $exception = new \Exception('Permanent failure');

    $job->failed($exception);

    Log::shouldHaveReceived('error')
        ->with('Auto Filer: ProcessFileOperations job PERMANENTLY failed', [
            'change_manifest' => $manifest->toArray(),
        ]);
});

it('configures middleware correctly', function () {
    config()->set('auto-filer.throttle_exception_attempts', 5);
    config()->set('auto-filer.throttle_exception_period', 3);

    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);

    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(2);
    expect($middleware[0])->toBeInstanceOf(ThrottlesExceptions::class);
    expect($middleware[1])->toBeInstanceOf(RateLimited::class);
});

it('uses configured queue connection and queue', function () {
    config()->set('auto-filer.queue_connection', 'redis');
    config()->set('auto-filer.queue', 'files');

    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);

    expect($job->connection)->toBe('redis');
    expect($job->queue)->toBe('files');
});
