<?php

namespace christopheraseidl\HasUploads\Tests;

use christopheraseidl\HasUploads\HasUploadsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'christopheraseidl\\HasUploads\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Rollback any migrations that might not have reset due to test error and exit.
        $this->artisan('migrate:reset');

        // Custom migration.
        $this->loadMigrationsFrom(__DIR__.'/TestMigrations/create_test_models_table.php');

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
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:reset');

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            HasUploadsServiceProvider::class,
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
        config()->set('queue.batching.database', 'testing');

        // Run jobs synchronously.
        config()->set('queue.default', 'sync');
        config()->set('queue.connections.sync', [
            'driver' => 'sync',
        ]);

        // Don't actually broadcast events.
        config()->set('broadcasting.default', 'null');
        config()->set('broadcasting.connections.null', [
            'driver' => 'null',
        ]);
    }
}
