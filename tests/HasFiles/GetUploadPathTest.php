<?php

namespace christopheraseidl\ModelFiler\Tests\ModelFiler;

use christopheraseidl\ModelFiler\Facades\FileService;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config()->set('model-filer.path', 'uploads');

    $this->model = TestModel::factory()->create();
});

it('gets the correct path without assetType', function () {
    $path = $this->model->getUploadPath();
    $expectedPath = "uploads/{$this->model->getModelDirName()}/{$this->model->id}";

    expect($path)->toBe($expectedPath);
});

it('gets the correct path with assetType', function () {
    $imagePath = $this->model->getUploadPath('images');
    $expectedImagePath = "uploads/{$this->model->getModelDirName()}/{$this->model->id}/images";
    $documentPath = $this->model->getUploadPath('documents');
    $expectedDocumentPath = FileService::getPath()."/{$this->model->getModelDirName()}/{$this->model->id}/documents";

    expect($imagePath)->toBe($expectedImagePath);
    expect($documentPath)->toBe($expectedDocumentPath);
});

it('throws an exception for incorrect assetType', function () {
    $nonExistent = 'non-existent';
    $class = $this->model::class;

    expect(fn () => $this->model->getUploadPath($nonExistent))
        ->toThrow(\Exception::class, "Asset type '{$nonExistent}' is not configured for {$class}");
});
