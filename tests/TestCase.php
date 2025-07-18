<?php

namespace christopheraseidl\AutoFiler\Tests;

use christopheraseidl\AutoFiler\AutoFilerServiceProvider;
use christopheraseidl\CircuitBreaker\Laravel\CircuitBreakerServiceProvider;
use Intervention\Image\Laravel\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Queue table migrations.
        if (app()->version() < 11) {
            $this->artisan('queue:table');
            $this->artisan('queue:batches-table');
            $this->artisan('queue:failed-table');
        } else {
            $this->artisan('make:queue-table');
            $this->artisan('make:queue-batches-table');
            $this->artisan('make:queue-failed-table');
        }

        $this->artisan('migrate');

        // Custom migration.
        $this->loadMigrationsFrom(__DIR__.'/TestMigrations/create_test_models_table.php');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            CircuitBreakerServiceProvider::class,
            ServiceProvider::class,
            AutoFilerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Set the database up for testing.
        config()->set('database.default', 'testing');

        // Ensure that the database runs in memory.
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Force job batches to use the testing connection.
        config()->set('queue.default', 'database');
    }
}
