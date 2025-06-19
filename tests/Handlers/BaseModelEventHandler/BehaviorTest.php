<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\BaseModelEventHandler;

use christopheraseidl\ModelFiler\Handlers\Services\BatchManager;
use christopheraseidl\ModelFiler\Handlers\Services\ModelFileChangeTracker;
use christopheraseidl\ModelFiler\Jobs\Services\Builder;
use christopheraseidl\ModelFiler\Services\FileService;
use christopheraseidl\ModelFiler\Tests\TestClasses\BaseModelEventHandlerTestClass;
use christopheraseidl\ModelFiler\Tests\TestTraits\BaseModelEventHandlerHelpers;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;

uses(
    BaseModelEventHandlerHelpers::class
);

/**
 * Tests BaseModelEventHandler class method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\BaseModelEventHandler
 */
beforeEach(function () {
    Bus::fake();

    $this->setHandler();

    $this->mock(FileService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getDisk')->andReturn($this->diskTestValue);
    });

    $this->mock(Builder::class);
    $this->batchManager = $this->mock(BatchManager::class);
    $this->mock(ModelFileChangeTracker::class);

    $this->handler = \Mockery::mock(BaseModelEventHandlerTestClass::class, [
        app(FileService::class),
        app(Builder::class),
        $this->batchManager,
        app(ModelFileChangeTracker::class),
    ])->makePartial();

    $this->handler->shouldAllowMockingProtectedMethods();

    $this->jobs = [
        $this->mock('Job1'),
        $this->mock('Job2'),
    ];

    $this->model = $this->partialMock($this->model::class, function ($mock) {
        $mock->shouldReceive('getUploadableAttributes')
            ->andReturn([
                'image' => 'image',
                'document' => 'document',
            ]);
    });
});

it('dispatches batch with jobs when handle is called', function () {
    $this->handler->shouldReceive('getAllJobs')->once()->andReturn($this->jobs);
    $this->handler->shouldReceive('getBatchDescription')->once()->andReturn('Test description');
    $this->batchManager->shouldReceive('dispatch')
        ->once()
        ->with($this->jobs, $this->model, $this->diskTestValue, 'Test description');

    $this->handler->handle($this->model);
});

it('returns all jobs when no filter is applied', function () {
    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'image', 'image')
        ->andReturn([$this->jobs[0]]);

    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'document', 'document')
        ->andReturn([$this->jobs[1]]);

    $jobs = $this->handler->getAllJobs($this->model);

    expect($jobs)->toHaveCount(2)
        ->and($jobs)->toEqual($this->jobs);
});

it('filters jobs based on provided criteria', function (array $expectedJobIndexes, ?\Closure $filter = null) {
    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'image', 'image')
        ->andReturn([$this->jobs[0]]);

    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'document', 'document')
        ->andReturn([$this->jobs[1]]);

    $jobs = $this->handler->getAllJobs($this->model, $filter);
    $expectedJobs = array_map(fn ($index) => $this->jobs[$index], $expectedJobIndexes);

    expect($jobs)->toEqual($expectedJobs);
})->with([
    'no filter' => [
        [0, 1], // Expected job indexes
        null, // Null value for edge cases
    ],
    'filter images' => [
        [0], // Only one expected job index
        fn ($model, $attribute) => $attribute === 'image', // Filter by attribute name 'image'
    ],
]);
