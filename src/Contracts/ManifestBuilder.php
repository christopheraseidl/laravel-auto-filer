<?php

namespace christopheraseidl\ModelFiler\Contracts;

use christopheraseidl\ModelFiler\ValueObjects\ChangeManifest;
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
}
