<?php

namespace christopheraseidl\ModelFiler\Tests\Exceptions;

use christopheraseidl\ModelFiler\Exceptions\ModelFilerException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tests ModelFilerException integration scenarios and edge cases.
 *
 * @covers \christopheraseidl\ModelFiler\Exceptions\ModelFilerException
 */
test('handles missing app config gracefully', function () {
    config()->set('app.name', null);
    config()->set('app.env', null);

    $exception = new ModelFilerException('Test', 0, null, true, 'test@example.com');

    Mail::shouldReceive('raw')
        ->once()
        ->withArgs(function ($content) {
            return str_contains($content, 'An exception occurred in  ():');
        });

    $exception->report();
});

test('exception can be thrown and caught', function () {
    expect(function () {
        throw new ModelFilerException('Test exception');
    })->toThrow(ModelFilerException::class, 'Test exception');
});

test('exception maintains inheritance chain', function () {
    $exception = new ModelFilerException;

    expect($exception)->toBeInstanceOf(\Exception::class)
        ->and($exception)->toBeInstanceOf(\Throwable::class);
});

test('exception with previous exception maintains chain', function () {
    $original = new \Exception('Original error');
    $wrapper = new ModelFilerException('Wrapped error', 500, $original);

    expect($wrapper->getPrevious())->toBe($original)
        ->and($wrapper->getPrevious()->getMessage())->toBe('Original error');
});

test('mail subject includes exception class name', function () {
    config()->set('app.name', 'MyApp');
    $exception = new ModelFilerException('Test', 0, null, true, 'test@example.com');

    Mail::shouldReceive('raw')
        ->once()
        ->withArgs(function ($content, $closure) {
            $mail = \Mockery::mock();
            $mail->shouldReceive('to')->with('test@example.com')->andReturnSelf();
            $mail->shouldReceive('subject')->once()->withArgs(function ($subject) {
                return $subject === '[MyApp] Exception: '.ModelFilerException::class;
            });
            $closure($mail);

            return true;
        });

    $exception->report();
});

test('notification works with empty admin email string', function () {
    $exception = new ModelFilerException('Test', 0, null, true, '');

    Mail::shouldReceive('raw')->never();

    $exception->report();
});

test('logs to correct channel', function () {
    $exception = new ModelFilerException('Channel test');

    Log::shouldReceive('channel')->once()->with('stack')->andReturnSelf();
    Log::shouldReceive('error')->once()->withArgs(function ($message) {
        return $message === 'Channel test';
    });

    $exception->report();
});
