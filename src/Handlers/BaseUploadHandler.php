<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Contracts\BatchHandler;
use christopheraseidl\HasUploads\Contracts\JobFactory;
use christopheraseidl\HasUploads\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Traits\GetsClassBaseName;
use Illuminate\Database\Eloquent\Model;

abstract class BaseUploadHandler
{
    use GetsClassBaseName;

    protected string $disk;

    public function __construct(
        protected UploadService $uploadService,
        protected JobFactory $jobFactory,
        protected BatchHandler $batch,
        protected ModelFileChangeTracker $fileTracker
    ) {
        $this->disk = $uploadService->getDisk();
    }

    public function handle(Model $model): void
    {
        $jobs = $this->getAllJobs($model);

        if (empty($jobs)) {
            return;
        }

        $this->batch->dispatch(
            $jobs,
            $model,
            $this->disk,
            $this->getBatchDescription()
        );
    }

    abstract protected function getAllJobs(Model $model): array;

    abstract protected function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array;

    abstract protected function getBatchDescription(): string;
}
