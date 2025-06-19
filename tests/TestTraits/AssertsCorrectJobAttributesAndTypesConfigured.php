<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

use Illuminate\Support\Facades\Bus;

/**
 * A trait providing re-usable methods to make standard assertions about job batches.
 */
trait AssertsCorrectJobAttributesAndTypesConfigured
{
    public function assertCorrectJobAttributesAndTypesConfigured(
        int $expectedjobsWithStringCount = 1,
        int $expectedjobsWithArrayCount = 1
    ): void {
        Bus::assertBatched(function ($batch) use ($expectedjobsWithStringCount, $expectedjobsWithArrayCount) {
            // Attributes set up on christopheraseidl\ModelFiler\Tests\TestModels\TestModel
            $stringAttribute = 'string';
            $stringAttributeType = 'images';
            $arrayAttrribute = 'array';
            $arrayAttributeType = 'documents';

            // Get a list of job model attributes
            $jobAttributes = $batch->jobs->map(
                fn ($job) => $job->getPayload()->getModelAttribute()
            );

            // Get a count of jobs with model attributes called "string"
            $jobsWithStringCount = $jobAttributes->filter(
                fn ($attr) => $attr === $stringAttribute
            )->count();

            // Get a count of jobs with model attributes called "array"
            $jobsWithArrayCount = $jobAttributes->filter(
                fn ($attr) => $attr === $arrayAttrribute
            )->count();

            // Get a list of job model attribute types
            $jobAttributeTypes = $batch->jobs->map(
                fn ($job) => $job->getPayload()->getModelAttributeType()
            );

            // Get a count of jobs with "images" model attribute type
            $jobsWithImagesCount = $jobAttributeTypes->filter(
                fn ($type) => $type === $stringAttributeType
            )->count();

            // Get a count of jobs with "documents" model attribute type
            $jobsWithDocumentsCount = $jobAttributeTypes->filter(
                fn ($type) => $type === $arrayAttributeType
            )->count();

            // Assert based on parameters and christopheraseidl\ModelFiler\Tests\TestModels\TestModel
            return $jobAttributes->contains('string')
                && $jobAttributes->contains('array')
                && $jobAttributeTypes->contains('images')
                && $jobAttributeTypes->contains('documents')
                && $jobsWithStringCount === $expectedjobsWithStringCount
                && $jobsWithArrayCount === $expectedjobsWithArrayCount
                && $jobsWithImagesCount === $expectedjobsWithStringCount
                && $jobsWithDocumentsCount === $expectedjobsWithArrayCount;
        });
    }
}
