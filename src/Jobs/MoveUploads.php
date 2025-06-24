<?php

namespace christopheraseidl\ModelFiler\Jobs;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileMover;
use christopheraseidl\ModelFiler\Jobs\Contracts\MoveUploads as MoveUploadsContract;
use christopheraseidl\ModelFiler\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use christopheraseidl\ModelFiler\Support\FileOperationType;
use christopheraseidl\ModelFiler\Traits\HasFileMover;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Moves uploaded files from temporary locations to permanent model directories.
 */
class MoveUploads extends Job implements MoveUploadsContract
{
    use HasFileMover;

    public function __construct(
        private readonly MoveUploadsPayload $payload
    ) {
        $this->config();
    }

    /**
     * Execute file move operation within Job's handleJob() wrapper.
     */
    public function handle(): void
    {
        $this->handleJob(function () {
            $this->executeMove();
        });
    }

    /**
     * Execute file move operation within database transaction.
     */
    public function executeMove(): void
    {
        DB::transaction(function () {
            $model = $this->getPayload()->resolveModel();
            $attribute = $this->getPayload()->getModelAttribute();
            $originalFiles = Arr::wrap($model->{$attribute});
            $filesToMove = $this->getPayload()->getFilePaths();
            $unmovedFiles = $this->arrayDiff($originalFiles, $filesToMove);
            $movedFiles = $this->moveFiles($filesToMove);

            // Combine unmoved and moved files
            $model->{$attribute} = $this->arrayMerge($unmovedFiles, $movedFiles);
            $model->{$attribute} = $this->normalizeAttributeValue($model, $attribute);

            $model->saveQuietly(); // Skip model events during file move
        });
    }

    /**
     * Move an array of files and return their new paths.
     */
    public function moveFiles(array $filesToMove): array
    {
        return array_map(
            function ($oldPath) {
                return $this->getMover()->attemptMove(
                    $this->getPayload()->getDisk(),
                    $oldPath,
                    $this->getPayload()->getNewDir());
            },
            $filesToMove
        );
    }

    /**
     * Normalize attribute value based on model casting configuration.
     */
    public function normalizeAttributeValue(Model $model, string $attribute): string|array|null
    {
        if (! isset($model->{$attribute}) || ! is_array($model->{$attribute})) {
            return $model->{$attribute};
        }

        $casts = $model->getCasts();

        if (! isset($casts[$attribute]) || $casts[$attribute] !== 'array') {
            try {
                return (count($model->{$attribute}) === 1)
                    ? $model->{$attribute}[0] // Single file as string
                    : throw new \Exception('The attribute is being treated as an array but is not cast as an array in the model.');
            } catch (\Throwable $e) {
                Log::error("Array conversion failed in MoveUploads job: {$e->getMessage()}");
                throw $e;
            }
        }

        return $model->{$attribute};
    }

    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Move, OperationScope::File);
    }

    public function uniqueId(): string
    {
        return $this->getPayload()->getKey();
    }

    public function getPayload(): MoveUploadsPayload
    {
        return $this->payload;
    }

    /**
     * Get the array difference, remove empty values, and reindex result.
     */
    public function arrayDiff(array $array1, array $array2): array
    {
        return array_values(array_diff($array1, $array2));
    }

    /**
     * Merge arrays, remove empty values, and reindex result.
     */
    public function arrayMerge(array $array1, array $array2): array
    {
        return array_values(array_filter(
            array_merge($array1, $array2)
        ));
    }

    protected function config()
    {
        parent::config();

        $this->mover = app()->make(FileMover::class); // Initialize file mover service
    }
}
