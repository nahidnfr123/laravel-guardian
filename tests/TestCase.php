<?php

namespace NahidFerdous\Guardian\Tests;

use NahidFerdous\Guardian\Database\Seeders\TyroSeeder;
use NahidFerdous\Guardian\Providers\GuardianServiceProvider;
use NahidFerdous\Guardian\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Mockery;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra {
    protected bool $disableTyroCommands = false;

    protected bool $disableTyroApi = false;

    protected function setUp(): void {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'NahidFerdous\\Tyro\\Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });

        $this->artisan('migrate', ['--database' => 'testing'])->run();
        $this->artisan('db:seed', ['--class' => TyroSeeder::class])->run();
    }

    protected function defineDatabaseMigrations(): void {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');
    }

    protected function defineEnvironment($app): void {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('tyro.models.user', User::class);
        $app['config']->set('tyro.tables.users', (new User)->getTable());
        $app['config']->set('tyro.disable_commands', $this->disableTyroCommands);
        $app['config']->set('tyro.disable_api', $this->disableTyroApi);
    }

    protected function tearDown(): void {
        Mockery::close();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array {
        return [
            GuardianServiceProvider::class,
            \Laravel\Sanctum\SanctumServiceProvider::class,
        ];
    }
}
