<?php

namespace christopheraseidl\AutoFiler\Tests\Observers;

use christopheraseidl\AutoFiler\Contracts\ManifestBuilder;
use christopheraseidl\AutoFiler\Jobs\ProcessFileOperations;
use christopheraseidl\AutoFiler\Observers\ModelObserver;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModel;
use christopheraseidl\AutoFiler\ValueObjects\ChangeManifest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->manifestBuilder = $this->mock(ManifestBuilder::class);
    $this->observer = new ModelObserver($this->manifestBuilder);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
    config()->set('auto-filer.max_attempts', 1);
    config()->set('auto-filer.retry_delay', 0);
    config()->set('auto-filer.thumbnails.enabled', false);
    config()->set('auto-filer.retry_wait_seconds', 0); // to speed up tests

    Queue::fake();
    Log::spy();
});

it('handles model events', function ($event) {
    $model = new TestModel([
        'id' => 1,
    ]);

    $manifest = new ChangeManifest(collect(['operation1']));

    $this->manifestBuilder->shouldReceive('shouldBuildManifest')
        ->with($model, $event)
        ->andReturn(true);

    $this->manifestBuilder->shouldReceive('buildManifest')
        ->with($model, $event)
        ->andReturn($manifest);

    $this->observer->$event($model);

    Queue::assertPushed(ProcessFileOperations::class);
})->with([
    'created',
    'updated',
    'saved',
    'deleted',
    'forceDeleted',
]);

it('ignores models without HasAutoFiles trait', function () {
    $model = new class extends Model {};

    $this->manifestBuilder->shouldNotReceive('shouldBuildManifest');
    $this->manifestBuilder->shouldNotReceive('buildManifest');

    $this->observer->created($model);

    Queue::assertNothingPushed();
});

it('does not dispatch job when manifest has no operations', function () {
    $model = new TestModel;
    $manifest = new ChangeManifest(collect());

    $this->manifestBuilder->shouldReceive('shouldBuildManifest')
        ->with($model, 'created')
        ->andReturn(true);

    $this->manifestBuilder->shouldReceive('buildManifest')
        ->with($model, 'created')
        ->andReturn($manifest);

    $this->observer->created($model);

    Queue::assertNothingPushed();
});

it('skips manifest building when shouldBuildManifest returns false', function () {
    $model = new TestModel;

    $this->manifestBuilder->shouldReceive('shouldBuildManifest')
        ->with($model, 'updated')
        ->andReturn(false);

    $this->manifestBuilder->shouldNotReceive('buildManifest');

    $this->observer->updated($model);

    Queue::assertNothingPushed();
});

it('ignores non-allowed events', function () {
    $model = new TestModel;

    $this->manifestBuilder->shouldNotReceive('shouldBuildManifest');
    $this->manifestBuilder->shouldNotReceive('buildManifest');

    $this->observer->restoring($model);

    Queue::assertNothingPushed();
});

it('throws exception when non-model argument passed', function () {
    expect(fn () => $this->observer->created('not a model'))
        ->toThrow(\InvalidArgumentException::class);

    Log::assertLogged('error', function ($message) {
        return str_contains($message, 'Expected argument to be an instance of Illuminate\Database\Eloquent\Model');
    });
});

it('ignores calls with incorrect argument count', function () {
    $this->manifestBuilder->shouldNotReceive('shouldBuildManifest');
    $this->manifestBuilder->shouldNotReceive('buildManifest');

    // Call with no arguments
    $this->observer->created();

    // Call with multiple arguments
    $this->observer->created(new TestModel, 'extra');

    Queue::assertNothingPushed();
});

it('checks trait usage with class_uses_recursive', function () {
    // Create a model with inherited trait
    $childModel = new class extends TestModel {};

    $manifest = new ChangeManifest(collect(['operation1']));

    $this->manifestBuilder->shouldReceive('shouldBuildManifest')
        ->with($childModel, 'created')
        ->andReturn(true);

    $this->manifestBuilder->shouldReceive('buildManifest')
        ->with($childModel, 'created')
        ->andReturn($manifest);

    $this->observer->created($childModel);

    Queue::assertPushed(ProcessFileOperations::class);
});

it('handles multiple events in sequence', function () {
    $model = new TestModel;
    $manifest = new ChangeManifest(collect(['operation1']));

    $this->manifestBuilder->shouldReceive('shouldBuildManifest')
        ->times(3)
        ->andReturn(true);

    $this->manifestBuilder->shouldReceive('buildManifest')
        ->times(3)
        ->andReturn($manifest);

    $this->observer->created($model);
    $this->observer->updated($model);
    $this->observer->saved($model);

    Queue::assertPushed(ProcessFileOperations::class, 3);
});
