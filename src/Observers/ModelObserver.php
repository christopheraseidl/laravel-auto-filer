<?php

namespace christopheraseidl\ModelFiler\Observers;

use christopheraseidl\ModelFiler\Contracts\ManifestBuilder;
use christopheraseidl\ModelFiler\HasAutoFiles;
use christopheraseidl\ModelFiler\Jobs\ProcessFileOperations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ModelObserver
{
    private const ALLOWED_EVENTS = ['created', 'updated', 'saved', 'deleted', 'forceDeleted'];

    public function __construct(
        private ManifestBuilder $builder
    ) {}

    public function __call(string $method, array $arguments): void
    {
        if (! in_array($method, self::ALLOWED_EVENTS) || count($arguments) !== 1) {
            return;
        }

        if (! ($model = $arguments[0]) instanceof Model) {
            $errorMessage = "Expected argument to be an instance of Illuminate\Database\Eloquent\Model";
            Log::error($errorMessage);

            throw new \InvalidArgumentException($errorMessage);
        }

        $this->handleEvent($model, $method);
    }

    protected function handleEvent(Model $model, string $event): void
    {
        if (! $this->usesHasAutoFiles($model)) {
            return;
        }

        // Conditionally skip building the manifest (e.g. on a soft delete)
        if (! $this->builder->shouldBuildManifest($model, $event)) {
            return;
        }

        $manifest = $this->builder->buildManifest($model, $event);

        if ($manifest->operations->isNotEmpty()) {
            ProcessFileOperations::dispatch($manifest);
        }
    }

    private function usesHasAutoFiles(Model $model): bool
    {
        return in_array(HasAutoFiles::class, class_uses_recursive($model));
    }
}
