<?php

namespace christopheraseidl\ModelFiler\Tests\OrganizesFiles;

use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModelEmpty;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModelWithMethods;

it('registers model observer on boot', function () {
    $observers = TestModel::getObservableEvents();

    expect($observers)->toContain('saving', 'saved', 'deleting', 'deleted');
});

it('resolves file attributes from property', function () {
    $model = new TestModel;

    $attributes = $model->getFileAttributes();

    expect($attributes)->toBe(['avatar' => 'images', 'documents' => 'files']);
});

it('resolves file attributes from method when property missing', function () {
    $model = new TestModelWithMethods;

    $attributes = $model->getFileAttributes();

    expect($attributes)->toBe(['resume' => 'documents', 'portfolio' => 'files']);
});

it('returns empty array when no file configuration exists', function () {
    $model = new TestModelEmpty;

    $attributes = $model->getFileAttributes();

    expect($attributes)->toBe([]);
});

it('resolves rich text attributes from property', function () {
    $model = new TestModel;

    $attributes = $model->getRichTextAttributes();

    expect($attributes)->toBe(['description' => 'files']);
});

it('caches normalized file configuration', function () {
    $model = new TestModel;

    $first = $model->getFileAttributes();
    $second = $model->getFileAttributes();

    expect($first)->toBe($second);
    expect($model->normalizedFiles)->toBe($first);
});

it('caches normalized rich text configuration', function () {
    $model = new TestModel;

    $first = $model->getRichTextAttributes();
    $second = $model->getRichTextAttributes();

    expect($first)->toBe($second);
    expect($model->normalizedRichText)->toBe($first);
});

it('normalizes simple array configuration', function () {
    $model = new TestModel;

    // Test with simple array
    $config = ['avatar', 'documents'];
    $normalized = $model->normalizeConfig($config);

    expect($normalized)->toBe(['avatar' => 'files', 'documents' => 'files']);
});

it('preserves associative array configuration', function () {
    $model = new TestModel;

    $config = ['avatar' => 'images', 'documents' => 'files'];
    $normalized = $model->normalizeConfig($config);

    expect($normalized)->toBe(['avatar' => 'images', 'documents' => 'files']);
});

it('generates correct model directory name', function () {
    $model = new TestModel(['id' => 123]);

    expect($model->getModelDirName())->toBe('test_models');
});

it('generates correct model directory path', function () {
    $model = new TestModel(['id' => 123]);

    expect($model->getModelDir())->toBe('test_models/123');
});

it('generates correct file directory path', function () {
    $model = new TestModel(['id' => 123]);

    expect($model->getFileDir('avatar'))->toBe('test_models/123/images');
    expect($model->getFileDir('documents'))->toBe('test_models/123/files');
});

it('handles model without id in directory generation', function () {
    $model = new TestModel;

    expect($model->getModelDir())->toBe('test_models');
    expect($model->getFileDir('avatar'))->toBe('test_models/images');
});
