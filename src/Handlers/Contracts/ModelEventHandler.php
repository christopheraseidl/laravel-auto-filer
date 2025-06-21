<?php

namespace christopheraseidl\ModelFiler\Handlers\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Handles model events by creating and dispatching upload jobs.
 */
interface ModelEventHandler
{
    /**
     * Handle model events for file processing.
     */
    public function handle(Model $model): void;

    /**
     * Create jobs from model attribute for file processing.
     */
    public function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array;

    /**
     * Get batch description for job processing.
     */
    public function getBatchDescription(): string;

    /**
     * Get all jobs from uploadable attributes with optional filtering.
     */
    public function getAllJobs(Model $model, ?\Closure $filter = null): array;
}
