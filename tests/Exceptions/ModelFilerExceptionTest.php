<?php

namespace christopheraseidl\ModelFiler\Tests\Exceptions;

use christopheraseidl\ModelFiler\Exceptions\ModelFilerException;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tests ModelFilerException structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Exceptions\ModelFilerException
 */
beforeEach(function () {
    config()->set('model-filer.admin_email', 'admin@example.com');
    config()->set('app.name', 'TestApp');
    config()->set('app.env', 'testing');
});

test('constructor sets default values correctly', function () {
    $exception = new ModelFilerException;

    expect($exception->getMessage())->toBe('')
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

test('constructor accepts all parameters', function () {
    $previous = new \Exception('Previous exception');
    $exception = new ModelFilerException(
        'Test message',
        500,
        $previous,
        true,
        'custom@example.com'
    );

    expect($exception->getMessage())->toBe('Test message')
        ->and($exception->getCode())->toBe(500)
        ->and($exception->getPrevious())->toBe($previous);
});

test('constructor uses config admin email when not provided', function () {
    $exception = Reflect::on(new ModelFilerException('Test', 0, null, true));

    $adminEmail = $exception->adminEmail;

    expect($adminEmail)->toBe('admin@example.com');
});

test('constructor uses provided admin email over config', function () {
    $exception = Reflect::on(new ModelFilerException('Test', 0, null, true, 'override@example.com'));

    $adminEmail = $exception->adminEmail;

    $exception->report();

    expect($adminEmail)->toBe('override@example.com');
});

test('report method logs error', function () {
    $exception = new ModelFilerException('Test error message');

    Log::shouldReceive('channel')->with('stack')->andReturnSelf();
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Test error message'
                && $context['exception'] === ModelFilerException::class
                && isset($context['file'])
                && isset($context['line'])
                && isset($context['trace']);
        });

    $exception->report();
});

test('report method sends admin notification when enabled', function () {
    $exception = new ModelFilerException('Test error', 0, null, true);

    Mail::shouldReceive('raw')->once();

    $exception->report();
});

test('report method does not send notification when disabled', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('error');

    Mail::shouldReceive('raw')->never();

    $exception = new ModelFilerException('Test error', 0, null, false);
    $exception->report();
});

test('report method does not send notification when no admin email', function () {
    config()->set('model-filer.admin_email', null);
    $exception = new ModelFilerException('Test error', 0, null, true, null);

    Mail::shouldReceive('raw')->never();

    $exception->report();
});

test('mail content includes all expected information', function () {
    $exception = new ModelFilerException('Test error message', 0, null, true);

    Mail::shouldReceive('raw')
        ->once()
        ->withArgs(function ($mail) {
            return str_contains($mail, 'An exception occurred in TestApp (testing):')
                && str_contains($mail, 'Test error message')
                && str_contains($mail, 'Please check the logs for full details.')
                && str_contains($mail, 'Time: ');
        });

    $exception->report();
});

test('uses stack log channel by default', function () {
    $exception = new ModelFilerException('Test error');

    Log::shouldReceive('channel')->once()->with('stack')->andReturnSelf();
    Log::shouldReceive('error')->once();

    $exception->report();
});
