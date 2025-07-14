<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs;

use christopheraseidl\ModelFiler\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Contracts\FileMover;
use christopheraseidl\ModelFiler\Contracts\RichTextScanner;
use christopheraseidl\ModelFiler\Events\ProcessingComplete;
use christopheraseidl\ModelFiler\Events\ProcessingFailure;
use christopheraseidl\ModelFiler\Jobs\ProcessFileOperations;
use christopheraseidl\ModelFiler\Tests\Helpers\TestModel;
use christopheraseidl\ModelFiler\ValueObjects\ChangeManifest;
use christopheraseidl\ModelFiler\ValueObjects\FileOperation;
use christopheraseidl\ModelFiler\ValueObjects\OperationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mover = $this->mock(FileMover::class);
    $this->deleter = $this->mock(FileDeleter::class);
    $this->scanner = $this->mock(RichTextScanner::class);

    Event::fake();
    Log::fake();
});

it('processes move operations and updates model attributes', function () {
    $model = TestModel::create(['avatar' => 'temp/old-file.jpg']);

    $operation = new FileOperation(
        type: OperationType::Move,
        source: 'temp/old-file.jpg',
        destination: 'images/new-file.jpg',
        modelClass: TestModel::class,
        modelId: $model->id,
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

    $operation = new FileOperation(
        type: OperationType::Move,
        source: 'temp/doc1.pdf',
        destination: 'files/doc1.pdf',
        modelClass: TestModel::class,
        modelId: $model->id,
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

    $operation = new FileOperation(
        type: OperationType::MoveRichText,
        source: 'temp/image.jpg',
        destination: 'images/image.jpg',
        modelClass: TestModel::class,
        modelId: $model->id,
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
    $operation = new FileOperation(
        type: OperationType::Delete,
        source: 'temp/delete-me.jpg',
        destination: null
    );

    $manifest = new ChangeManifest(collect([$operation]));

    $this->deleter->shouldReceive('delete')
        ->once()
        ->with('temp/delete-me.jpg');

    $job = new ProcessFileOperations($manifest);
    $job->handle($this->mover, $this->deleter, $this->scanner);

    Event::assertDispatched(ProcessingComplete::class);
});

it('processes multiple operations in single transaction', function () {
    $model = TestModel::create(['avatar' => 'temp/avatar.jpg']);

    $moveOperation = new FileOperation(
        type: OperationType::Move,
        source: 'temp/avatar.jpg',
        destination: 'images/avatar.jpg',
        modelClass: TestModel::class,
        modelId: $model->id,
        attribute: 'avatar'
    );

    $deleteOperation = new FileOperation(
        type: OperationType::Delete,
        source: 'temp/old-file.jpg',
        destination: null
    );

    $manifest = new ChangeManifest(collect([$moveOperation, $deleteOperation]));

    $this->mover->shouldReceive('move')->once();
    $this->deleter->shouldReceive('delete')->once();

    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function ($callback) {
            return $callback();
        });

    $job = new ProcessFileOperations($manifest);
    $job->handle($this->mover, $this->deleter, $this->scanner);
});

it('handles exceptions and dispatches failure event', function () {
    $operation = new FileOperation(
        type: OperationType::Delete,
        source: 'temp/file.jpg',
        destination: null
    );

    $manifest = new ChangeManifest(collect([$operation]));

    $exception = new \Exception('File operation failed');
    $this->deleter->shouldReceive('delete')->andThrow($exception);

    $job = new ProcessFileOperations($manifest);

    expect(fn () => $job->handle($this->mover, $this->deleter, $this->scanner))
        ->toThrow(\Exception::class, 'File operation failed');

    Event::assertDispatched(ProcessingFailure::class, function ($event) use ($exception) {
        return $event->exception === $exception;
    });

    Log::shouldHaveReceived('error')
        ->with('Model Filer: ProcessFileOperations job temporarily failed', ['error' => 'File operation failed']);
});

it('throws exception when model not found', function () {
    $operation = new FileOperation(
        type: OperationType::Move,
        source: 'temp/file.jpg',
        destination: 'images/file.jpg',
        modelClass: TestModel::class,
        modelId: 999,
        attribute: 'avatar'
    );

    $manifest = new ChangeManifest(collect([$operation]));

    $this->mover->shouldReceive('move')->once();

    $job = new ProcessFileOperations($manifest);

    expect(fn () => $job->handle($this->mover, $this->deleter, $this->scanner))
        ->toThrow(\RuntimeException::class, 'Model not found: '.TestModel::class.'#999');
});

it('generates unique job ID based on operations', function () {
    $operation1 = new FileOperation(
        type: OperationType::Move,
        source: 'temp/a.jpg',
        destination: 'images/a.jpg'
    );

    $operation2 = new FileOperation(
        type: OperationType::Delete,
        source: 'temp/b.jpg',
        destination: null
    );

    $manifest = new ChangeManifest(collect([$operation1, $operation2]));
    $job = new ProcessFileOperations($manifest);

    $expectedSignature = collect(['Move:temp/a.jpg:images/a.jpg', 'Delete:temp/b.jpg:'])
        ->sort()
        ->join('|');

    $expectedId = ProcessFileOperations::class.'_'.hash('sha256', $expectedSignature);

    expect($job->uniqueId())->toBe($expectedId);
});

it('configures retry timeout to 5 minutes', function () {
    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);

    expect($job->retryUntil())->toEqual(now()->addMinutes(5));
});

it('logs permanent failure', function () {
    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);
    $exception = new \Exception('Permanent failure');

    $job->failed($exception);

    Log::shouldHaveReceived('error')
        ->with('Model Filer: ProcessFileOperations job PERMANENTLY failed', [
            'change_manifest' => $manifest->toArray(),
        ]);
});

it('configures middleware correctly', function () {
    config()->set('model-filer.throttle_exception_attempts', 5);
    config()->set('model-filer.throttle_exception_period', 3);

    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);

    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(2);
    expect($middleware[0])->toBeInstanceOf(ThrottlesExceptions::class);
    expect($middleware[1])->toBeInstanceOf(RateLimited::class);
});

it('uses configured queue connection and queue', function () {
    config()->set('model-filer.queue_connection', 'redis');
    config()->set('model-filer.queue', 'files');

    $manifest = new ChangeManifest(collect());
    $job = new ProcessFileOperations($manifest);

    expect($job->connection)->toBe('redis');
    expect($job->queue)->toBe('files');
});
