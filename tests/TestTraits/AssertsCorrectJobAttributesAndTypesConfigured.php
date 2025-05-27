<?php

namespace christopheraseidl\HasUploads\Tests\TestTraits;

use Illuminate\Support\Facades\Bus;

/**
 * A trait providing re-usable methods for tests in
 * /tests/Handlers/ModelUpdateHandler and /tests/Handlers/ModelCreationHandler.
 */
trait AssertsCorrectJobAttributesAndTypesConfigured
{
    public function assertCorrectJobAttributesAndTypesConfigured(
        int $expectedStringCount = 1,
        int $expectedArrayCount = 1
    ): void {
        Bus::assertBatched(function ($batch) use ($expectedStringCount, $expectedArrayCount) {
            $attributes = $batch->jobs->map(
                fn ($job) => $job->getPayload()->getModelAttribute()
            );

            $stringCount = $attributes->filter(
                fn ($attr) => $attr === 'string'
            )->count();

            $arrayCount = $attributes->filter(
                fn ($attr) => $attr === 'array'
            )->count();

            $types = $batch->jobs->map(
                fn ($job) => $job->getPayload()->getModelAttributeType()
            );

            $imagesCount = $types->filter(
                fn ($type) => $type === 'images'
            )->count();

            $documentsCount = $types->filter(
                fn ($type) => $type === 'documents'
            )->count();

            return $attributes->contains('string')
                && $attributes->contains('array')
                && $types->contains('images')
                && $types->contains('documents')
                && $stringCount === $expectedStringCount
                && $arrayCount === $expectedArrayCount
                && $imagesCount === $expectedStringCount
                && $documentsCount === $expectedArrayCount;
        });
    }
}
