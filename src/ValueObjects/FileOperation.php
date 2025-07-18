<?php

namespace christopheraseidl\AutoFiler\ValueObjects;

use Illuminate\Database\Eloquent\Model;

readonly class FileOperation
{
    private function __construct(
        public OperationType $type,
        public string $source,
        public ?string $destination = null,
        public ?string $modelClass = null,
        public ?int $modelId = null,
        public ?string $attribute = null
    ) {}

    public static function move(
        string $from,
        string $to,
        Model $model,
        string $attribute
    ): self {
        return new self(
            OperationType::Move,
            $from,
            $to,
            $model::class,
            $model->id,
            $attribute
        );
    }

    public static function delete(string $path): self
    {
        return new self(OperationType::Delete, $path);
    }

    public static function moveRichText(
        string $from,
        string $to,
        Model $model,
        string $attribute
    ): self {
        return new self(
            OperationType::MoveRichText,
            $from,
            $to,
            $model::class,
            $model->id,
            $attribute
        );
    }
}
