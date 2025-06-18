<?php

namespace christopheraseidl\HasUploads\Support;

use christopheraseidl\HasUploads\Support\Contracts\UploadableAttributesBuilder as UploadableAttributesBuilderContract;

/**
 * Defines and returns uploadable attributes and their types.
 */
class UploadableAttributesBuilder implements UploadableAttributesBuilderContract
{
    public function __construct(
        private array $attributes = [],
        private ?string $currentAttribute = null
    ) {}

    /**
     * Start defining an uploadable attribute.
     */
    public function uploadable(string $attribute): self
    {
        $this->currentAttribute = $attribute;

        return $this;
    }

    /**
     * Define the asset type for the current attribute.
     */
    public function as(string $assetType): self
    {
        if ($this->currentAttribute === null) {
            throw new \InvalidArgumentException('No attribute defined. Call uploadable() first.');
        }

        $this->attributes[$this->currentAttribute] = $assetType;
        $this->currentAttribute = null;

        return $this;
    }

    /**
     * Continue defining another uploadable attribute.
     */
    public function and(string $attribute): self
    {
        return $this->uploadable($attribute);
    }

    /**
     * Get the built attributes array.
     */
    public function build(): array
    {
        if ($this->currentAttribute !== null) {
            throw new \InvalidArgumentException("Attribute '{$this->currentAttribute}' is missing its asset type. Call as() to complete the definition.");
        }

        return $this->attributes;
    }

    public function toArray(): array
    {
        return $this->build();
    }
}
