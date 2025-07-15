<?php

namespace christopheraseidl\AutoFiler\Contracts;

use christopheraseidl\AutoFiler\ValueObjects\ChangeManifest;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds a manifest of file operations to be processed.
 */
interface ManifestBuilder
{
    /**
     * Build a manifest of file operations to be processed.
     */
    public function buildManifest(Model $model, string $event): ChangeManifest;

    /**
     * Determine whether manifest building should proceed.
     */
    public function shouldBuildManifest(Model $model, string $event): bool;
}
