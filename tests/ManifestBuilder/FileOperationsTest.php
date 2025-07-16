<?php

namespace christopheraseidl\AutoFiler\Tests\ManifestBuilder;

use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use christopheraseidl\AutoFiler\Services\ManifestBuilderService;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModel;
use christopheraseidl\AutoFiler\ValueObjects\OperationType;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->scanner = $this->mock(RichTextScanner::class);
    $this->scanner->shouldReceive('extractPaths')->andReturn(collect());
    $this->service = new ManifestBuilderService($this->scanner);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
});

it('creates move operations for new files', function () {
    $model = new TestModel([
        'id' => 1,
    ]);

    $model->avatar = 'temp/new_avatar.jpg';

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(1);

    $operation = $manifest->operations->first();
    expect($operation->type)->toBe(OperationType::Move);
    expect($operation->source)->toBe('temp/new_avatar.jpg');
    expect($operation->destination)->toBe('test_models/1/images/new_avatar.jpg');
    expect($operation->modelClass)->toBe(TestModel::class);
    expect($operation->modelId)->toBe(1);
    expect($operation->attribute)->toBe('avatar');
});

it('creates delete operations for removed files', function () {
    $model = new TestModel([
        'id' => 1,
        'avatar' => 'test_models/1/images/old_avatar.jpg',
    ]);
    $model->syncOriginal(); // Manually set above value as original
    $model->avatar = null;

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(1);

    $operation = $manifest->operations->first();
    expect($operation->type)->toBe(OperationType::Delete);
    expect($operation->source)->toBe('test_models/1/images/old_avatar.jpg');
    expect($operation->destination)->toBeNull();
});

it('handles array file attributes', function () {
    $model = new TestModel([
        'id' => 1,
    ]);
    $model->syncOriginal();
    $model->documents = ['temp/doc1.pdf', 'temp/doc2.pdf'];

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(2);

    $operations = $manifest->operations->all();
    expect($operations[0]->source)->toBe('temp/doc1.pdf');
    expect($operations[0]->destination)->toBe('test_models/1/files/doc1.pdf');
    expect($operations[1]->source)->toBe('temp/doc2.pdf');
    expect($operations[1]->destination)->toBe('test_models/1/files/doc2.pdf');
});

it('generates unique destination paths', function () {
    Storage::disk('public')->put('test_models/1/images/avatar.jpg', 'existing');
    Storage::disk('public')->put('test_models/1/images/avatar_1.jpg', 'existing');

    $model = new TestModel([
        'id' => 1,
    ]);
    $model->syncOriginal();
    $model->avatar = 'temp/avatar.jpg';

    $manifest = $this->service->buildManifest($model, 'updated');

    $operation = $manifest->operations->first();
    expect($operation->destination)->toBe('test_models/1/images/avatar_2.jpg');
});

it('handles multiple attribute updates', function () {
    $model = new TestModel([
        'id' => 1,
    ]);
    $model->syncOriginal();
    $model->avatar = 'temp/avatar.jpg';
    $model->documents = ['temp/doc.pdf'];

    $manifest = $this->service->buildManifest($model, 'updated');

    $operations = $manifest->operations->all();
    expect($operations[0]->destination)->toBe('test_models/1/images/avatar.jpg');
    expect($operations[1]->destination)->toBe('test_models/1/files/doc.pdf');
});

it('handles mixed add and remove operations', function () {
    $model = new TestModel([
        'id' => 1,
        'documents' => ['test_models/1/files/old_doc.pdf'],
    ]);
    $model->syncOriginal();

    $model->documents = ['temp/new_doc.pdf'];

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(2);

    $operations = $manifest->operations->all();

    // New file
    expect($operations[0]->type)->toBe(OperationType::Move);
    expect($operations[0]->source)->toBe('temp/new_doc.pdf');

    // Old file
    expect($operations[1]->type)->toBe(OperationType::Delete);
    expect($operations[1]->source)->toBe('test_models/1/files/old_doc.pdf');
});

it('ignores unchanged files', function () {
    $model = new TestModel([
        'id' => 1,
        'avatar' => 'test_models/1/images/avatar.jpg',
    ]);
    $model->syncOriginal();

    $model->avatar = 'test_models/1/images/avatar.jpg';

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toBeEmpty();
});
