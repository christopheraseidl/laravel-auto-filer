<?php

namespace christopheraseidl\AutoFiler\Tests\ManifestBuilder;

use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use christopheraseidl\AutoFiler\Exceptions\AutoFilerException;
use christopheraseidl\AutoFiler\Services\ManifestBuilderService;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModel;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModelWithSoftDeletion;
use christopheraseidl\AutoFiler\ValueObjects\ChangeManifest;
use christopheraseidl\AutoFiler\ValueObjects\OperationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->scanner = $this->mock(RichTextScanner::class);
    $this->service = new ManifestBuilderService($this->scanner);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
});

it('builds manifest for model creation', function () {
    $model = new TestModel([
        'id' => 1,
        'avatar' => 'temp/avatar.jpg',
        'documents' => ['temp/doc1.pdf', 'temp/doc2.pdf'],
    ]);

    $manifest = $this->service->buildManifest($model, 'created');

    expect($manifest)->toBeInstanceOf(ChangeManifest::class);
    expect($manifest->operations)->toHaveCount(3);

    $operations = $manifest->operations->all();
    expect($operations[0]->type)->toBe(OperationType::Move);
    expect($operations[0]->source)->toBe('temp/avatar.jpg');
    expect($operations[0]->destination)->toBe('test_models/1/images/avatar.jpg');
});

it('builds manifest for model updates', function () {
    $model = new TestModel([
        'id' => 1,
        'avatar' => 'test_models/1/images/old_avatar.jpg',
    ]);
    $model->syncOriginal(); // Manually set above, new value as original
    $model->avatar = 'temp/new_avatar.jpg';

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(2);

    $operations = $manifest->operations->all();

    // New file to move
    expect($operations[0]->type)->toBe(OperationType::Move);
    expect($operations[0]->source)->toBe('temp/new_avatar.jpg');

    // Old file to delete
    expect($operations[1]->type)->toBe(OperationType::Delete);
    expect($operations[1]->source)->toBe('test_models/1/images/old_avatar.jpg');
});

it('builds delete manifest for permanent deletion', function () {
    $model = new TestModel(['id' => 1]);

    $manifest = $this->service->buildManifest($model, 'deleted');

    expect($manifest->operations)->toHaveCount(1);

    $operation = $manifest->operations->first();
    expect($operation->type)->toBe(OperationType::Delete);
    expect($operation->source)->toBe('test_models/1');
});

it('builds delete manifest for force deletion', function () {
    $model = new TestModelWithSoftDeletion(['id' => 1]);

    $manifest = $this->service->buildManifest($model, 'forceDeleted');

    expect($manifest->operations)->toHaveCount(1);

    $operation = $manifest->operations->first();
    expect($operation->type)->toBe(OperationType::Delete);
    expect($operation->source)->toBe('test_model_with_soft_deletions/1');
});

it('skips manifest building for soft deletion', function () {
    $model = new TestModelWithSoftDeletion(['id' => 1]);

    $shouldBuild = $this->service->shouldBuildManifest($model, 'deleted');

    expect($shouldBuild)->toBeFalse();
});

it('determines when manifest building should proceed', function () {
    $model = new TestModel(['id' => 1]);

    expect($this->service->shouldBuildManifest($model, 'created'))->toBeTrue();
    expect($this->service->shouldBuildManifest($model, 'updated'))->toBeTrue();
    expect($this->service->shouldBuildManifest($model, 'deleted'))->toBeTrue();
    expect($this->service->shouldBuildManifest($model, 'forceDeleted'))->toBeTrue();
});

it('throws exception when model lacks HasAutoFiles trait', function () {
    $model = new class extends Model
    {
        protected $table = 'test_models';

        protected $fillable = ['id'];
    };
    $model::create(['id' => 1]);

    expect(fn () => $this->service->buildManifest($model, 'created'))
        ->toThrow(AutoFilerException::class, "The model {$model} must use the 'HasAutoFiles' trait.");
});

it('handles models without id', function () {
    $model = new TestModel([
        'avatar' => 'temp/avatar.jpg',
    ]);

    $manifest = $this->service->buildManifest($model, 'created');

    $operation = $manifest->operations->first();
    expect($operation->destination)->toBe('test_models/images/avatar.jpg');
});

it('handles empty file attributes', function () {
    $model = new TestModel(['id' => 1]);

    $manifest = $this->service->buildManifest($model, 'created');

    expect($manifest->operations)->toBeEmpty();
});

it('extracts model data correctly', function () {
    $model = new TestModel(['id' => 1]);

    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('extractModelData');
    $method->setAccessible(true);
    $method->invoke($this->service, $model);

    $modelDir = $reflection->getProperty('modelDir');
    $modelDir->setAccessible(true);
    expect($modelDir->getValue($this->service))->toBe('test_models/1');

    $fileAttributes = $reflection->getProperty('modelFileAttributes');
    $fileAttributes->setAccessible(true);
    expect($fileAttributes->getValue($this->service))->toBe([
        'avatar' => 'images',
        'documents' => 'files',
    ]);
});
