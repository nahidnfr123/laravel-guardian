<?php

namespace NahidFerdous\Shield\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mockery;
use NahidFerdous\Shield\Database\Seeders\ShieldSeeder;
use NahidFerdous\Shield\Providers\ShieldServiceProvider;
use NahidFerdous\Shield\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected bool $disableShieldCommands = false;

    protected bool $disableShieldApi = false;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'NahidFerdous\\Shield\\Database\\Factories\\'.class_basename($modelName).'Factory';
        });

        $this->artisan('migrate', ['--database' => 'testing'])->run();
        $this->artisan('db:seed', ['--class' => ShieldSeeder::class])->run();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('shield.models.user', User::class);
        $app['config']->set('shield.tables.users', (new User)->getTable());
        $app['config']->set('shield.disable_commands', $this->disableShieldCommands);
        $app['config']->set('shield.disable_api', $this->disableShieldApi);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ShieldServiceProvider::class,
            \Laravel\Sanctum\SanctumServiceProvider::class,
        ];
    }
}
