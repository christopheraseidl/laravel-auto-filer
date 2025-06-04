<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\BaseModelEventHandler;

use christopheraseidl\HasUploads\Handlers\BaseModelEventHandler;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Services\Contracts\UploadService;
use Illuminate\Database\Eloquent\Model;

/**
 * Tests BaseModelEventHandler class structure, methods, and their signatures.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\BaseModelEventHandler
 */
beforeEach(function () {
    $this->base = new \ReflectionClass(BaseModelEventHandler::class);
});

it('is an abstract class', function () {
    expect($this->base->isAbstract())->toBeTrue();
});

it('contains createJobsFromAttribute abstract method with correct signature', function () {
    $method = $this->base->getMethod('createJobsFromAttribute');
    $expectedParameters = [
        ['model', Model::class],
        ['attribute', 'string'],
        ['type', '?string', true],  // Third param is optional (allows null)
    ];

    foreach ($expectedParameters as $key => $parameter) {
        $param = $method->getParameters()[$key];

        expect($param->getName())->toBe($parameter[0])
            ->and((string) $param->getType())->toBe($parameter[1]);

        if (isset($parameter[2])) {
            expect($param->allowsNull())->toBe($parameter[2]);
        }
    }

    expect((string) $method->getReturnType())->toBe('?array');
});

it('contains getBatchDescription abstract method with correct signature', function () {
    $methodName = 'getBatchDescription';
    $method = $this->base->getMethod($methodName);

    expect($method->isAbstract())->toBeTrue()
        ->and($method->isProtected())->toBeTrue()
        ->and($method->getParameters())->toHaveCount(0)
        ->and($method->getReturnType())->not->toBeNull()
        ->and($method->getReturnType()->getName())->toBe('string');
});

it('contains a constructor with correct signature', function () {
    $constructor = $this->base->getConstructor();
    $expectedTypes = [
        UploadService::class,
        Builder::class,
        BatchManager::class,
        ModelFileChangeTracker::class,
    ];

    foreach ($expectedTypes as $key => $type) {
        $param = $constructor->getParameters()[$key];

        expect($param->getType()->getName())->toBe($type);
    }
});

it('contains handle method with correct signature', function () {
    $methodName = 'handle';
    $method = $this->base->getMethod($methodName);
    $parameters = $method->getParameters();
    $param = $parameters[0];
    $returnType = $method->getReturnType();

    expect($method->isAbstract())->toBeFalse()
        ->and($method->isPublic())->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($param->getName())->toBe('model')
        ->and($param->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe('void');
});

it('contains getAllJobs method with correct signature', function () {
    $methodName = 'getAllJobs';
    $method = $this->base->getMethod($methodName);
    $expectedParameters = [
        ['model', Model::class],
        ['filter', '?Closure', true],  // Third param is optional (allows null)
    ];

    foreach ($expectedParameters as $key => $parameter) {
        $param = $method->getParameters()[$key];

        expect($param->getName())->toBe($parameter[0])
            ->and((string) $param->getType())->toBe($parameter[1]);

        if (isset($parameter[2])) {
            expect($param->allowsNull())->toBe($parameter[2]);
        }
    }

    expect($method->isAbstract())->toBeFalse()
        ->and($method->isProtected())->toBeTrue()
        ->and($method->getReturnType())->not->toBeNull()
        ->and($method->getReturnType()->getName())->toBe('array');
});
