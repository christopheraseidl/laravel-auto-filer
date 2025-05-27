<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\HasUploads\Facades\UploadService;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');

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
    $expectedDocumentPath = UploadService::getPath()."/{$this->model->getModelDirName()}/{$this->model->id}/documents";

    expect($imagePath)->toBe($expectedImagePath);
    expect($documentPath)->toBe($expectedDocumentPath);
});

it('throws an exception for incorrect assetType', function () {
    $path = $this->model->getUploadPath('non-existent');
})->throws(\Exception::class, "The asset type 'non-existent' does not exist.");
