<?php

namespace christopheraseidl\HasUploads\Tests\Support;

use christopheraseidl\HasUploads\Support\UploadableAttributesBuilder;
use christopheraseidl\Reflect\Reflect;

beforeEach(function () {
    $this->builder = new UploadableAttributesBuilder;
});

it('sets the current attribute and returns self', function () {
    $attribute = 'avatar';

    $result = $this->builder->uploadable($attribute);
    $currentAttribute = Reflect::on($this->builder)->currentAttribute;

    expect($result)->toBe($this->builder);
    expect($currentAttribute)->toBe($attribute);
});

it('defines the asset type for the current attribute and returns self', function () {
    $reflection = Reflect::on($this->builder);
    $reflection->currentAttribute = 'avatar';

    $result = $reflection->as('images');

    expect($result)->toBe($this->builder);
    expect($reflection->attributes)->toHaveKey('avatar');
    expect($reflection->attributes)->toContain('images');
});

it('throws an error when calling as if current attribute is not defined', function () {
    expect(fn () => $this->builder->as('images'))
        ->toThrow(\Exception::class, 'No attribute defined. Call uploadable() first.');
});

it('can chain additional attributes', function () {
    $reflection = Reflect::on($this->builder);
    $reflection->currentAttribute = 'avatar';
    $attribute = 'resume';

    $result = $this->builder->and($attribute);

    expect($result)->toBe($this->builder);
    expect($reflection->currentAttribute)->toBe($attribute);
});

it('returns the $attributes array', function () {
    $array = [
        'avatar' => 'images',
        'resume' => 'documents',
    ];

    $reflection = Reflect::on($this->builder);
    $reflection->attributes = $array;

    expect($reflection->build())->toBe($array);
    expect($reflection->toArray())->toBe($array);
});

it('throws an error when building without a current attribute asset type', function () {
    $attribute = 'avatar';

    $reflection = Reflect::on($this->builder);
    $reflection->currentAttribute = $attribute;

    expect(fn () => $reflection->build())
        ->toThrow(\InvalidArgumentException::class, "Attribute '{$attribute}' is missing its asset type. Call as() to complete the definition.");
});
