<?php

namespace NahidFerdous\Guardian\Providers;

use NahidFerdous\Guardian\Console\Commands\AboutCommand;
use NahidFerdous\Guardian\Console\Commands\AddPrivilegeCommand;
use NahidFerdous\Guardian\Console\Commands\AddRoleCommand;
use NahidFerdous\Guardian\Console\Commands\AssignRoleCommand;
use NahidFerdous\Guardian\Console\Commands\AttachPrivilegeCommand;
use NahidFerdous\Guardian\Console\Commands\CreateUserCommand;
use NahidFerdous\Guardian\Console\Commands\DeletePrivilegeCommand;
use NahidFerdous\Guardian\Console\Commands\DeleteRoleCommand;
use NahidFerdous\Guardian\Console\Commands\DeleteUserCommand;
use NahidFerdous\Guardian\Console\Commands\DeleteUserRoleCommand;
use NahidFerdous\Guardian\Console\Commands\DetachPrivilegeCommand;
use NahidFerdous\Guardian\Console\Commands\DocCommand;
use NahidFerdous\Guardian\Console\Commands\FlushRolesCommand;
use NahidFerdous\Guardian\Console\Commands\InstallCommand;
use NahidFerdous\Guardian\Console\Commands\ListPrivilegesCommand;
use NahidFerdous\Guardian\Console\Commands\ListRolesCommand;
use NahidFerdous\Guardian\Console\Commands\ListRolesWithPrivilegesCommand;
use NahidFerdous\Guardian\Console\Commands\ListUsersCommand;
use NahidFerdous\Guardian\Console\Commands\ListUsersWithRolesCommand;
use NahidFerdous\Guardian\Console\Commands\LoginCommand;
use NahidFerdous\Guardian\Console\Commands\LogoutAllCommand;
use NahidFerdous\Guardian\Console\Commands\LogoutAllUsersCommand;
use NahidFerdous\Guardian\Console\Commands\LogoutCommand;
use NahidFerdous\Guardian\Console\Commands\MeCommand;
use NahidFerdous\Guardian\Console\Commands\PostmanCollectionCommand;
use NahidFerdous\Guardian\Console\Commands\PrepareUserModelCommand;
use NahidFerdous\Guardian\Console\Commands\PublishConfigCommand;
use NahidFerdous\Guardian\Console\Commands\PublishMigrationsCommand;
use NahidFerdous\Guardian\Console\Commands\PurgePrivilegesCommand;
use NahidFerdous\Guardian\Console\Commands\QuickTokenCommand;
use NahidFerdous\Guardian\Console\Commands\RoleUsersCommand;
use NahidFerdous\Guardian\Console\Commands\SeedCommand;
use NahidFerdous\Guardian\Console\Commands\SeedPrivilegesCommand;
use NahidFerdous\Guardian\Console\Commands\SeedRolesCommand;
use NahidFerdous\Guardian\Console\Commands\StarCommand;
use NahidFerdous\Guardian\Console\Commands\SuspendedUsersCommand;
use NahidFerdous\Guardian\Console\Commands\SuspendUserCommand;
use NahidFerdous\Guardian\Console\Commands\UnsuspendUserCommand;
use NahidFerdous\Guardian\Console\Commands\UpdatePrivilegeCommand;
use NahidFerdous\Guardian\Console\Commands\UpdateRoleCommand;
use NahidFerdous\Guardian\Console\Commands\UpdateUserCommand;
use NahidFerdous\Guardian\Console\Commands\UserPrivilegesCommand;
use NahidFerdous\Guardian\Console\Commands\UserRolesCommand;
use NahidFerdous\Guardian\Console\Commands\VersionCommand;

use NahidFerdous\Guardian\Http\Middleware\EnsureAnyGuardianPrivilege;
use NahidFerdous\Guardian\Http\Middleware\EnsureAnyGuardianRole;
use NahidFerdous\Guardian\Http\Middleware\EnsureGuardianPrivilege;
use NahidFerdous\Guardian\Http\Middleware\EnsureGuardianRole;
use NahidFerdous\Guardian\Http\Middleware\GuardianLog;
use NahidFerdous\Guardian\Models\Privilege;
use NahidFerdous\Guardian\Models\Role;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

class GuardianServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->mergeConfigFrom(__DIR__ . '/../../config/tyro.php', 'tyro');
    }

    public function boot(): void {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerBindings();
        $this->registerCommands();

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->loadFactoriesFrom(__DIR__ . '/../../database/factories');
        }
    }

    protected function registerRoutes(): void {
        if (config('tyro.disable_api', false)) {
            return;
        }

        if (!config('tyro.load_default_routes', true)) {
            return;
        }

        Route::group([
            'prefix' => trim(config('tyro.route_prefix', 'api'), '/'),
            'middleware' => config('tyro.route_middleware', ['api']),
            'as' => config('tyro.route_name_prefix', 'tyro.'),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        });
    }

    protected function registerMiddleware(): void {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('tyro.log', GuardianLog::class);
        $router->aliasMiddleware('privilege', EnsureGuardianPrivilege::class);
        $router->aliasMiddleware('privileges', EnsureAnyGuardianPrivilege::class);
        $router->aliasMiddleware('role', EnsureGuardianRole::class);
        $router->aliasMiddleware('roles', EnsureAnyGuardianRole::class);

        if (!array_key_exists('ability', $router->getMiddleware())) {
            $router->aliasMiddleware('ability', CheckForAnyAbility::class);
        }

        if (!array_key_exists('abilities', $router->getMiddleware())) {
            $router->aliasMiddleware('abilities', CheckAbilities::class);
        }
    }

    protected function registerBindings(): void {
        Route::model('role', Role::class);
        Route::model('privilege', Privilege::class);

        Route::bind('user', function ($value) {
            $userClass = config('tyro.models.user', config('auth.providers.users.model'));

            return $userClass::query()->findOrFail($value);
        });
    }

    protected function registerPublishing(): void {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../config/tyro.php' => config_path('tyro.php'),
        ], 'tyro-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'tyro-migrations');

        $this->publishes([
            __DIR__ . '/../../database/seeders/' => database_path('seeders'),
            __DIR__ . '/../../database/factories/' => database_path('factories'),
        ], 'tyro-database');

        $this->publishes([
            __DIR__ . '/../../resources/' => resource_path('vendor/tyro'),
        ], 'tyro-assets');
    }

    protected function registerCommands(): void {
        if (!$this->app->runningInConsole() || config('tyro.disable_commands', false)) {
            return;
        }

        $this->commands([
            AddRoleCommand::class,
            AddPrivilegeCommand::class,
            AboutCommand::class,
            AttachPrivilegeCommand::class,
            AssignRoleCommand::class,
            CreateUserCommand::class,
            DocCommand::class,
            DeleteRoleCommand::class,
            DeleteUserRoleCommand::class,
            DeleteUserCommand::class,
            DetachPrivilegeCommand::class,
            FlushRolesCommand::class,
            InstallCommand::class,
            ListPrivilegesCommand::class,
            ListRolesCommand::class,
            ListRolesWithPrivilegesCommand::class,
            ListUsersCommand::class,
            ListUsersWithRolesCommand::class,
            LoginCommand::class,
            LogoutAllCommand::class,
            LogoutAllUsersCommand::class,
            LogoutCommand::class,
            MeCommand::class,
            PrepareUserModelCommand::class,
            PurgePrivilegesCommand::class,
            PublishConfigCommand::class,
            PostmanCollectionCommand::class,
            PublishMigrationsCommand::class,
            QuickTokenCommand::class,
            SuspendUserCommand::class,
            SuspendedUsersCommand::class,
            UnsuspendUserCommand::class,
            RoleUsersCommand::class,
            DeletePrivilegeCommand::class,
            SeedCommand::class,
            SeedPrivilegesCommand::class,
            SeedRolesCommand::class,
            StarCommand::class,
            UpdatePrivilegeCommand::class,
            UpdateRoleCommand::class,
            UpdateUserCommand::class,
            UserPrivilegesCommand::class,
            UserRolesCommand::class,
            VersionCommand::class,

        ]);
    }
}
