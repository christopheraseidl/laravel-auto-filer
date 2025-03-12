<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads as MoveUploadsContract;
use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use christopheraseidl\HasUploads\Traits\AttemptsFileMoves;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

final class MoveUploads extends Job implements MoveUploadsContract
{
    use AttemptsFileMoves;

    public function __construct(
        private readonly MoveUploadsPayload $payload
    ) {}

    public function handle(): void
    {
        $model = $this->getPayload()->resolveModel();

        $this->handleJob(function () use ($model) {
            $attribute = $this->getPayload()->getModelAttribute();
            $model->{$attribute} = array_map(
                function ($oldPath) {
                    return $this->attemptMove(
                        $this->getPayload()->getDisk(),
                        $oldPath,
                        $this->getPayload()->getNewDir());
                },
                $this->getPayload()->getFilePaths()
            );

            $model->{$attribute} = $this->normalizeAttributeValue($model, $attribute);

            $model->saveQuietly();
        });
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
