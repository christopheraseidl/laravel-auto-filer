<?php

namespace christopheraseidl\AutoFiler\Tests\HasAutoFiles;

use christopheraseidl\AutoFiler\Tests\TestModels\TestModel;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModelEmpty;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModelWithMethods;

it('registers model observer on boot', function () {
    $model = new TestModel;
    $observers = $model->getObservableEvents();

    expect($observers)->toContain('created', 'updated', 'saved', 'deleted', 'forceDeleted');
});

it('registers model observer using observe method', function () {
    $model = new TestModel;
    $model::bootOrganizesFiles();
    $observers = $model->getObservableEvents();

    expect($observers)->toContain('created', 'updated', 'saved', 'deleted', 'forceDeleted');
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

it('resolves rich text attributes from method when property missing', function () {
    $model = new TestModelWithMethods;

    $attributes = $model->getRichTextAttributes();

    expect($attributes)->toBe(['bio' => 'files']);
});

it('returns empty array when no rich text configuration exists', function () {
    $model = new TestModelEmpty;

    $attributes = $model->getRichTextAttributes();

    expect($attributes)->toBe([]);
});

it('caches normalized file configuration', function () {
    $model = new TestModel;

    $first = $model->getFileAttributes();
    $second = $model->getFileAttributes();

    expect($first)->toBe($second);
    expect($model->getNormalizedFiles())->toBe($first);
});

it('caches normalized rich text configuration', function () {
    $model = new TestModel;

    $first = $model->getRichTextAttributes();
    $second = $model->getRichTextAttributes();

    expect($first)->toBe($second);
    expect($model->getNormalizedRichText())->toBe($first);
});

it('normalizes simple array configuration', function () {
    $model = new TestModel;

    // Test with simple array
    $config = ['avatar', 'documents'];
    $normalized = $model->getNormalizedConfig($config);

    expect($normalized)->toBe(['avatar' => 'files', 'documents' => 'files']);
});

it('preserves associative array configuration', function () {
    $model = new TestModel;

    $config = ['avatar' => 'images', 'documents' => 'files'];
    $normalized = $model->getNormalizedConfig($config);

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
