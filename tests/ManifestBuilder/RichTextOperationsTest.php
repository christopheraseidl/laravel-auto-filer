<?php

namespace christopheraseidl\AutoFiler\Tests\ManifestBuilder;

use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use christopheraseidl\AutoFiler\Services\ManifestBuilderService;
use christopheraseidl\AutoFiler\Tests\TestModels\TestModel;
use christopheraseidl\AutoFiler\ValueObjects\OperationType;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->scanner = $this->mock(RichTextScanner::class);
    $this->service = new ManifestBuilderService($this->scanner);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
});

it('creates move operations for rich text files', function () {
    $model = new TestModel([
        'id' => 1,
    ]);

    $model->description = '<p>Multiple files: <img src="temp/img1.jpg"> <a href="temp/doc.pdf">Doc</a></p>';

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with($model->description)
        ->andReturn(collect(['temp/img1.jpg', 'temp/doc.pdf']));

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with(null)
        ->andReturn(collect());

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(2);

    $operations = $manifest->operations->all();

    expect($operations[0]->type)->toBe(OperationType::MoveRichText);
    expect($operations[0]->source)->toBe('temp/img1.jpg');
    expect($operations[0]->destination)->toBe('test_models/1/files/img1.jpg');
    expect($operations[0]->modelClass)->toBe(TestModel::class);
    expect($operations[0]->modelId)->toBe(1);
    expect($operations[0]->attribute)->toBe('description');

    expect($operations[1]->type)->toBe(OperationType::MoveRichText);
    expect($operations[1]->source)->toBe('temp/doc.pdf');
    expect($operations[1]->destination)->toBe('test_models/1/files/doc.pdf');
});

it('creates rich text move operations for a newly created model', function () {
    $model = TestModel::create([
        'id' => 1,
        'description' => '<img src="temp/image.jpg">',
    ]);

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->andReturn(collect(['temp/image.jpg']));

    $manifest = $this->service->buildManifest($model, 'created');
    $operation = $manifest->operations->first();

    $operation = $manifest->operations->first();
    expect($operation->type)->toBe(OperationType::Move);
    expect($operation->destination)->toBe('test_models/1/files/image.jpg');
});

it('creates rich text delete operations for eliminated files', function () {
    $model = new TestModel([
        'id' => 1,
        'description' => '<img src="test_models/1/files/image.jpg">',
    ]);
    $model->description = null;

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with(null)
        ->andReturn(collect());

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with($model->description)
        ->andReturn(collect(['test_models/1/files/image.jpg']));

    $manifest = $this->service->buildManifest($model, 'created');
    $operation = $manifest->operations->first();
    expect($operation->type)->toBe(OperationType::Delete);
    expect($operation->source)->toBe('test_models/1/files/image.jpg');
});

it('handles rich text with no file references', function () {
    $model = new TestModel([
        'id' => 1,
        'description' => '<p>Just plain text content with no files</p>',
    ]);
    $model->syncOriginal(); // Manually set above value as original

    $this->scanner->shouldReceive('extractPaths')
        ->twice()
        ->with('<p>Just plain text content with no files</p>')
        ->andReturn(collect());

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toBeEmpty();
});

it('handles null rich text content', function () {
    $model = new TestModel([
        'id' => 1,
        'description' => null,
    ]);

    $this->scanner->shouldReceive('extractPaths')
        ->twice()
        ->with(null)
        ->andReturn(collect());

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toBeEmpty();
});

it('generates unique paths for rich text files', function () {
    Storage::disk('public')->put('test_models/1/files/image.jpg', 'existing');

    $model = new TestModel([
        'id' => 1,
    ]);
    $model->description = '<img src="temp/image.jpg">';

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->andReturn(collect(['temp/image.jpg']));

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with(null)
        ->andReturn(collect());

    $manifest = $this->service->buildManifest($model, 'updated');

    $operation = $manifest->operations->first();
    expect($operation->destination)->toBe('test_models/1/files/image_1.jpg');
});

it('processes both regular and rich text attributes', function () {
    $model = new TestModel([
        'id' => 1,
    ]);
    $model->avatar = 'temp/avatar.jpg';
    $model->description = '<img src="temp/content_image.jpg">';

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with('<img src="temp/content_image.jpg">')
        ->andReturn(collect(['temp/content_image.jpg']));

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with(null)
        ->andReturn(collect());

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(2);

    $operations = $manifest->operations->all();

    // Regular file operation
    expect($operations[0]->type)->toBe(OperationType::Move);
    expect($operations[0]->source)->toBe('temp/avatar.jpg');
    expect($operations[0]->attribute)->toBe('avatar');

    // Rich text operation
    expect($operations[1]->type)->toBe(OperationType::MoveRichText);
    expect($operations[1]->source)->toBe('temp/content_image.jpg');
    expect($operations[1]->attribute)->toBe('description');
});

it('handles complex rich text with multiple file types', function () {
    $model = new TestModel([
        'id' => 1,
    ]);
    $model->description = '
        <p>Article with media:</p>
        <img src="temp/hero.jpg" alt="Hero">
        <p>Download: <a href="temp/document.pdf">PDF</a></p>
        <video src="temp/video.mp4"></video>
    ';

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with($model->description)
        ->andReturn(collect([
            'temp/hero.jpg',
            'temp/document.pdf',
            'temp/video.mp4',
        ]));

    $this->scanner->shouldReceive('extractPaths')
        ->once()
        ->with(null)
        ->andReturn(collect());

    $manifest = $this->service->buildManifest($model, 'updated');

    expect($manifest->operations)->toHaveCount(3);

    $operations = $manifest->operations->all();
    expect($operations[0]->destination)->toBe('test_models/1/files/hero.jpg');
    expect($operations[1]->destination)->toBe('test_models/1/files/document.pdf');
    expect($operations[2]->destination)->toBe('test_models/1/files/video.mp4');
});
