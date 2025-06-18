<?php

namespace christopheraseidl\HasUploads\Support\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Defines and returns uploadable attributes and their types.
 */
interface UploadableAttributesBuilder extends Arrayable
{
    /**
     * Start defining an uploadable attribute.
     */
    public function uploadable(string $attribute): self;

    /**
     * Define the asset type for the current attribute.
     */
    public function as(string $assetType): self;

    /**
     * Continue defining another uploadable attribute.
     */
    public function and(string $attribute): self;

    /**
     * Get the built attributes array.
     */
    public function build(): array;
}
