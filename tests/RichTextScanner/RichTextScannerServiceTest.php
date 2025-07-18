<?php

namespace christopheraseidl\AutoFiler\Tests\RichTextScanner;

use christopheraseidl\AutoFiler\Services\RichTextScannerService;

beforeEach(function () {
    $this->service = new RichTextScannerService;

    config()->set('auto-filer.temp_directory', 'uploads/temp');
    config()->set('auto-filer.extensions', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'mp4', 'mp3']);
    config()->set('app.url', 'myapp.com');
});

it('extracts file paths from HTML content', function () {
    $content = '<p>Image: <img src="uploads/temp/image.jpg"> and link: <a href="uploads/temp/document.pdf">Doc</a></p>';

    $paths = $this->service->extractPaths($content);

    expect($paths->toArray())->toBe([
        'uploads/temp/image.jpg',
        'uploads/temp/document.pdf',
    ]);
});

it('filters manageable paths from temp directory', function () {
    $content = '
        <img src="https://myapp.com/uploads/temp/temp_image.jpg">
        <img src="permanent/image.jpg">
        <a href="uploads/temp/temp_doc.pdf">Temp Doc</a>
        <a href="https://external.com/file.jpg">External</a>
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths->toArray())->toContain(
        'uploads/temp/temp_image.jpg',
        'uploads/temp/temp_doc.pdf',
    );
});

it('identifies manageable paths correctly', function () {
    expect($this->service->isManageablePath('uploads/temp/file.jpg'))->toBeTrue();
    expect($this->service->isManageablePath('https://external.com/file.jpg'))->toBeFalse();
    expect($this->service->isManageablePath('permanent/file.jpg'))->toBeFalse();
});

it('normalizes paths correctly', function (?string $sitePath) {
    if ($sitePath) {
        config()->set('app.url', 'myapp.com/site/path');
    }

    $content = '
        <img src="/'.$sitePath.'storage/uploads/temp/image.jpg">
        <img src="'.$sitePath.'storage/uploads/temp/image2.jpg">
        <img src="'.$sitePath.'uploads/temp/image3.jpg">
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths->toArray())->toContain('uploads/temp/image.jpg');
    expect($paths->toArray())->toContain('uploads/temp/image2.jpg');
    expect($paths->toArray())->toContain('uploads/temp/image3.jpg');
})->with([
    'no site path' => null,
    'site path' => 'site/path/',
]);

it('updates paths in content with replacements', function () {
    $content = '<p>Check out <img src="uploads/temp/old.jpg"> and <a href="uploads/temp/doc.pdf">this</a></p>';

    $replacements = [
        'uploads/temp/old.jpg' => 'final/new.jpg',
        'uploads/temp/doc.pdf' => 'final/document.pdf',
    ];

    $updated = $this->service->updatePaths($content, $replacements);

    expect($updated)->toBe('<p>Check out <img src="final/new.jpg"> and <a href="final/document.pdf">this</a></p>');
});

it('handles various HTML attributes', function () {
    $content = '
        <img src="uploads/temp/image.jpg">
        <a href="uploads/temp/document.pdf">Link</a>
        <video src="uploads/temp/video.mp4"></video>
        <audio src="uploads/temp/audio.mp3"></audio>
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths)->toHaveCount(4);
    expect($paths->toArray())->toContain('uploads/temp/image.jpg');
    expect($paths->toArray())->toContain('uploads/temp/document.pdf');
    expect($paths->toArray())->toContain('uploads/temp/video.mp4');
    expect($paths->toArray())->toContain('uploads/temp/audio.mp3');
});

it('deduplicates extracted paths', function () {
    $content = '
        <img src="uploads/temp/image.jpg">
        <a href="uploads/temp/image.jpg">Same file</a>
        <img src="uploads/temp/image.jpg">
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths)->toHaveCount(1);
    expect($paths->first())->toBe('uploads/temp/image.jpg');
});

it('validates supported file extensions', function () {
    $content = '
        <img src="uploads/temp/image.jpg">
        <img src="uploads/temp/photo.png">
        <a href="uploads/temp/document.pdf">PDF</a>
        <a href="uploads/temp/unsupported.xyz">Unsupported</a>
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths->toArray())->toBe([
        'uploads/temp/image.jpg',
        'uploads/temp/photo.png',
        'uploads/temp/document.pdf',
    ]);
});

it('handles malformed HTML gracefully', function () {
    $content = '
        <img src  =    "uploads/temp/image.jpg"
        <a   href="uploads/temp/doc.pdf"   >Unclosed
        <img src="uploads/temp/another.png">
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths)->toContain('uploads/temp/image.jpg');
    expect($paths)->toContain('uploads/temp/doc.pdf');
    expect($paths)->toContain('uploads/temp/another.png');
});

it('handles quotes in attribute values', function () {
    $content = '
        <img src="uploads/temp/image.jpg">
        <img src=\'uploads/temp/single.jpg\'>
        <a href="uploads/temp/doc.pdf">Link</a>
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths->toArray())->toBe([
        'uploads/temp/image.jpg',
        'uploads/temp/single.jpg',
        'uploads/temp/doc.pdf',
    ]);
});

it('handles case-insensitive extension matching', function () {
    $content = '
        <img src="uploads/temp/lower.jpg">
        <img src="uploads/temp/upper.JPG">
        <img src="uploads/temp/mixed.JpG">
        <a href="uploads/temp/doc.PDF">Document</a>
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths)->toHaveCount(4);
    expect($paths->toArray())->toContain('uploads/temp/lower.jpg');
    expect($paths->toArray())->toContain('uploads/temp/upper.JPG');
    expect($paths->toArray())->toContain('uploads/temp/mixed.JpG');
    expect($paths->toArray())->toContain('uploads/temp/doc.PDF');
});

it('handles empty content', function () {
    $paths = $this->service->extractPaths('');

    expect($paths)->toBeEmpty();
});

it('handles null content', function () {
    $paths = $this->service->extractPaths(null);

    expect($paths)->toBeEmpty();
});

it('handles content with no file references', function () {
    $content = '<p>Just some plain text content without any file references.</p>';

    $paths = $this->service->extractPaths($content);

    expect($paths)->toBeEmpty();
});

it('normalizes URL-encoded paths', function () {
    $content = '<img src="uploads/temp/file%20with%20spaces.jpg">';

    $paths = $this->service->extractPaths($content);

    expect($paths->first())->toBe('uploads/temp/file%20with%20spaces.jpg');
});

it('ignores fragments and anchors in URLs', function () {
    $content = '
        <img src="uploads/temp/image.jpg#fragment">
        <a href="uploads/temp/doc.pdf#section1">Document</a>
        <img src="uploads/temp/photo.png?v=123#anchor">
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths->toArray())->toBe([
        'uploads/temp/image.jpg',
        'uploads/temp/doc.pdf',
        'uploads/temp/photo.png',
    ]);
});

it('handles paths with subdirectories', function () {
    $content = '
        <img src="uploads/temp/images/photo.jpg">
        <img src="uploads/temp/gallery/2024/january/pic.png">
        <a href="uploads/temp/documents/reports/annual.pdf">Report</a>
    ';

    $paths = $this->service->extractPaths($content);

    expect($paths->toArray())->toBe([
        'uploads/temp/images/photo.jpg',
        'uploads/temp/gallery/2024/january/pic.png',
        'uploads/temp/documents/reports/annual.pdf',
    ]);
});

it('returns false for external URLs with different hosts', function () {
    // Set up reflection
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('isLocalPath');
    $method->setAccessible(true);

    // Test with various external URL formats
    expect($method->invoke($this->service, 'https://external.com/file.jpg'))->toBeFalse();
    expect($method->invoke($this->service, 'http://another-domain.com/path/to/file.pdf'))->toBeFalse();
    expect($method->invoke($this->service, '//cdn.example.com/image.png'))->toBeFalse();

    // Also test with subdomains of different hosts
    expect($method->invoke($this->service, 'https://sub.external.com/file.jpg'))->toBeFalse();
});
