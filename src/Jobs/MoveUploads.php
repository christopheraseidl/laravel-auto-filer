<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\Contracts\FileMover;
use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads as MoveUploadsContract;
use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to move uploaded files from temporary locations to permanent model directories.
 *
 * This job handles the transition of files from temporary upload locations to their
 * final destinations associated with specific models. It updates the model's file
 * attribute to reflect the new file paths while preserving any unmoved files.
 *
 * The operation is wrapped in a database transaction to ensure data consistency
 * between the file system operations and model updates.
 */
final class MoveUploads extends Job implements MoveUploadsContract
{
    protected FileMover $mover;

    public function __construct(
        private readonly MoveUploadsPayload $payload
    ) {
        $this->config();
    }

    /**
     * Execute the file move operation within a database transaction.
     *
     * Moves specified files to their new directory and updates the model's
     * file attribute to reflect both moved and unmoved files. The database
     * transaction ensures consistency between file operations and model updates.
     */
    public function handle(): void
    {
        $this->handleJob(function () {
            DB::transaction(function () {
                $model = $this->getPayload()->resolveModel();
                $attribute = $this->getPayload()->getModelAttribute();
                $originalFiles = Arr::wrap($model->{$attribute});
                $filesToMove = $this->getPayload()->getFilePaths();
                $unmovedFiles = array_diff($originalFiles, $filesToMove);

                $movedFiles = array_map(
                    function ($oldPath) {
                        return $this->mover->attemptMove(
                            $this->getPayload()->getDisk(),
                            $oldPath,
                            $this->getPayload()->getNewDir());
                    },
                    $filesToMove
                );

                $model->{$attribute} = $this->arrayMerge($unmovedFiles, $movedFiles);
                $model->{$attribute} = $this->normalizeAttributeValue($model, $attribute);

                $model->saveQuietly();
            });
        });
    }

    /**
     * Normalize attribute value based on model casting configuration.
     *
     * Handles the conversion between array and string representations based on
     * the model's cast configuration. If the attribute is not cast as array
     * but contains multiple files, throws an exception to prevent data loss.
     *
     * @return string|array|null The normalized attribute value
     *
     * @throws \Exception When multiple files exist but attribute is not cast as array
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
                    ? $model->{$attribute}[0]
                    : throw new \Exception('The attribute is being treated as an array but is not cast as an array in the model.');
            } catch (\Throwable $e) {
                Log::error("Array conversion failed in MoveUploads job: {$e->getMessage()}");
                throw $e;
            }
        }

        return $model->{$attribute};
    }

    /**
     * Get the operation type identifier for this job.
     *
     * @return string The operation type for grouping and tracking
     */
    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Move, OperationScope::File);
    }

    /**
     * Get the operation type identifier for this job.
     *
     * @return string The operation type for grouping and tracking
     */
    public function uniqueId(): string
    {
        return $this->getPayload()->getKey();
    }

    public function getPayload(): MoveUploadsPayload
    {
        return $this->payload;
    }

    /**
     * Configure the job by setting up the FileMover dependency.
     *
     * Resolves the FileMover service from the container, which provides
     * the retry logic and circuit breaker integration for move operations.
     */
    protected function config()
    {
        parent::config();

        $this->mover = app()->make(FileMover::class);
    }

    /**
     * Merge arrays, remove empty values, and reindex the result.
     *
     * Combines unmoved and moved files while filtering out any null/empty
     * values that might result from failed move operations.
     *
     * @return array Clean, reindexed array of file paths
     */
    private function arrayMerge(array $array1, array $array2): array
    {
        return array_values(array_filter(
            array_merge($array1, $array2)
        ));
    }
}
