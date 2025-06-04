<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads as MoveUploadsContract;
use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use christopheraseidl\HasUploads\Traits\AttemptsFileMoves;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class MoveUploads extends Job implements MoveUploadsContract
{
    use AttemptsFileMoves;

    public function __construct(
        private readonly MoveUploadsPayload $payload
    ) {
        $this->config();
    }

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
                        return $this->attemptMove(
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
     * Merge two arrays, remove empty values, and return the reindexed result.
     */
    private function arrayMerge(array $array1, array $array2): array
    {
        return array_values(array_filter(
            array_merge($array1, $array2)
        ));
    }

    /**
     * Convert an array attribute to a string if it is not cast as an array on
     * the model.
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
}
