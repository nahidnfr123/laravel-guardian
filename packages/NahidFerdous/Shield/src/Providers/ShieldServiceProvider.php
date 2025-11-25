<?php

namespace NahidFerdous\Shield\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use NahidFerdous\Shield\Console\Commands\AboutCommand;
use NahidFerdous\Shield\Console\Commands\AddPrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\AddRoleCommand;
use NahidFerdous\Shield\Console\Commands\AssignRoleCommand;
use NahidFerdous\Shield\Console\Commands\AttachPrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\CreateUserCommand;
use NahidFerdous\Shield\Console\Commands\DeletePrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\DeleteRoleCommand;
use NahidFerdous\Shield\Console\Commands\DeleteUserCommand;
use NahidFerdous\Shield\Console\Commands\DeleteUserRoleCommand;
use NahidFerdous\Shield\Console\Commands\DetachPrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\DocCommand;
use NahidFerdous\Shield\Console\Commands\FlushRolesCommand;
use NahidFerdous\Shield\Console\Commands\InstallCommand;
use NahidFerdous\Shield\Console\Commands\ListPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\ListRolesCommand;
use NahidFerdous\Shield\Console\Commands\ListRolesWithPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\ListUsersCommand;
use NahidFerdous\Shield\Console\Commands\ListUsersWithRolesCommand;
use NahidFerdous\Shield\Console\Commands\LoginCommand;
use NahidFerdous\Shield\Console\Commands\LogoutAllCommand;
use NahidFerdous\Shield\Console\Commands\LogoutAllUsersCommand;
use NahidFerdous\Shield\Console\Commands\LogoutCommand;
use NahidFerdous\Shield\Console\Commands\MeCommand;
use NahidFerdous\Shield\Console\Commands\PostmanCollectionCommand;
use NahidFerdous\Shield\Console\Commands\PrepareUserModelCommand;
use NahidFerdous\Shield\Console\Commands\PublishConfigCommand;
use NahidFerdous\Shield\Console\Commands\PublishMigrationsCommand;
use NahidFerdous\Shield\Console\Commands\PurgePrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\QuickTokenCommand;
use NahidFerdous\Shield\Console\Commands\RoleUsersCommand;
use NahidFerdous\Shield\Console\Commands\SeedCommand;
use NahidFerdous\Shield\Console\Commands\SeedPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\SeedRolesCommand;
use NahidFerdous\Shield\Console\Commands\StarCommand;
use NahidFerdous\Shield\Console\Commands\SuspendedUsersCommand;
use NahidFerdous\Shield\Console\Commands\SuspendUserCommand;
use NahidFerdous\Shield\Console\Commands\UnsuspendUserCommand;
use NahidFerdous\Shield\Console\Commands\UpdatePrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\UpdateRoleCommand;
use NahidFerdous\Shield\Console\Commands\UpdateUserCommand;
use NahidFerdous\Shield\Console\Commands\UserPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\UserRolesCommand;
use NahidFerdous\Shield\Console\Commands\VersionCommand;
use NahidFerdous\Shield\Http\Middleware\EnsureAnyShieldPrivilege;
use NahidFerdous\Shield\Http\Middleware\EnsureAnyShieldRole;
use NahidFerdous\Shield\Http\Middleware\EnsureShieldPrivilege;
use NahidFerdous\Shield\Http\Middleware\EnsureShieldRole;
use NahidFerdous\Shield\Http\Middleware\ShieldLog;
use NahidFerdous\Shield\Http\Requests\CreateUserRequest;
use NahidFerdous\Shield\Http\Requests\LoginRequest;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Models\Role;

class ShieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/shield.php', 'shield');
        $this->registerValidations();
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerBindings();
        $this->registerCommands();

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadFactoriesFrom(__DIR__.'/../../database/factories');
        }
    }

    protected function registerValidations(): void
    {
        // Bind the CreateUserRequest to the custom class from config
        $this->app->bind(CreateUserRequest::class, function ($app) {
            $customClass = config('shield.validation.create_user');

            // If custom class is set and different from default, use it
            if ($customClass && $customClass !== CreateUserRequest::class) {
                return $app->make($customClass);
            }

            // Otherwise use the default
            return $app->make(CreateUserRequest::class);
        });

        $this->app->bind(LoginRequest::class, function ($app) {
            $customClass = config('shield.validation.login.request_class');

            if ($customClass && $customClass !== LoginRequest::class) {
                return $app->make($customClass);
            }

            return $app->make(LoginRequest::class);
        });
    }

    protected function registerRoutes(): void
    {
        if (config('shield.disable_api', false)) {
            return;
        }

        if (! config('shield.load_default_routes', true)) {
            return;
        }

        Route::group([
            'prefix' => trim(config('shield.route_prefix', 'api'), '/'),
            'middleware' => config('shield.route_middleware', ['api']),
            'as' => config('shield.route_name_prefix', 'shield.'),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        });
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('shield.log', ShieldLog::class);
        $router->aliasMiddleware('privilege', EnsureShieldPrivilege::class);
        $router->aliasMiddleware('privileges', EnsureAnyShieldPrivilege::class);
        $router->aliasMiddleware('role', EnsureShieldRole::class);
        $router->aliasMiddleware('roles', EnsureAnyShieldRole::class);

        if (! array_key_exists('ability', $router->getMiddleware())) {
            $router->aliasMiddleware('ability', CheckForAnyAbility::class);
        }

        if (! array_key_exists('abilities', $router->getMiddleware())) {
            $router->aliasMiddleware('abilities', CheckAbilities::class);
        }
    }

    protected function registerBindings(): void
    {
        Route::model('role', Role::class);
        Route::model('privilege', Privilege::class);

        Route::bind('user', function ($value) {
            $userClass = config('shield.models.user', config('auth.providers.users.model'));

            return $userClass::query()->findOrFail($value);
        });
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../../config/shield.php' => config_path('shield.php'),
        ], 'shield-config');

        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'shield-migrations');

        $this->publishes([
            __DIR__.'/../../database/seeders/' => database_path('seeders'),
            __DIR__.'/../../database/factories/' => database_path('factories'),
        ], 'shield-database');

        $this->publishes([
            __DIR__.'/../../resources/' => resource_path('vendor/shield'),
        ], 'shield-assets');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole() || config('shield.disable_commands', false)) {
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
