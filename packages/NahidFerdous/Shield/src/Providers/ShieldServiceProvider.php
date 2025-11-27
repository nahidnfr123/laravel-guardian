<?php

namespace NahidFerdous\Shield\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\AboutCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\AddPrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\AddRoleCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\AssignRoleCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\AttachPrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\CreateUserCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\DeletePrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\DeleteRoleCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\DeleteUserCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\DeleteUserRoleCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\DetachPrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\FlushRolesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\ListPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\ListRolesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\ListRolesWithPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\ListUsersCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\ListUsersWithRolesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\LoginCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\LogoutAllCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\LogoutAllUsersCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\LogoutCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\MeCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\PurgePrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\QuickTokenCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\RoleUsersCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\SuspendedUsersCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\SuspendUserCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\UnsuspendUserCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\UpdatePrivilegeCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\UpdateRoleCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\UpdateUserCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\UserPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\UserRolesCommand;
use NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands\VersionCommand;
use NahidFerdous\Shield\Console\Commands\DocCommand;
use NahidFerdous\Shield\Console\Commands\InstallCommand;
use NahidFerdous\Shield\Console\Commands\PostmanCollectionCommand;
use NahidFerdous\Shield\Console\Commands\PrepareUserModelCommand;
use NahidFerdous\Shield\Console\Commands\PublishConfigCommand;
use NahidFerdous\Shield\Console\Commands\PublishExceptionHandlerCommand;
use NahidFerdous\Shield\Console\Commands\PublishMigrationsCommand;
use NahidFerdous\Shield\Console\Commands\SeedCommand;
use NahidFerdous\Shield\Console\Commands\SeedPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\SeedRolesCommand;
use NahidFerdous\Shield\Console\Commands\StarCommand;
use NahidFerdous\Shield\Console\Commands\SwitchAuthDriverCommand;
use NahidFerdous\Shield\Http\Middleware\EnsureAnyShieldPrivilege;
use NahidFerdous\Shield\Http\Middleware\EnsureAnyShieldRole;
use NahidFerdous\Shield\Http\Middleware\EnsureShieldPrivilege;
use NahidFerdous\Shield\Http\Middleware\EnsureShieldRole;
use NahidFerdous\Shield\Http\Middleware\JWTAuthenticate;
use NahidFerdous\Shield\Http\Middleware\ShieldLog;
use NahidFerdous\Shield\Http\Requests\ShieldCreateUserRequest;
use NahidFerdous\Shield\Http\Requests\ShieldLoginRequest;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;
use NahidFerdous\Shield\Services\SocialAuthService;

class ShieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/shield.php', 'shield');
        $this->app->register(ShieldEventServiceProvider::class);
        $this->registerValidations();
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerBindings();
        $this->registerCommands();
        $this->registerAuthDriver();
        $this->registerViews();

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadFactoriesFrom(__DIR__.'/../../database/factories');
        }
    }

    protected function registerViews(): void
    {
        $viewPath = __DIR__.'/../../resources/views';

        // Register shield views
        $this->loadViewsFrom($viewPath, 'shield');

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/shield'),
        ], 'shield-views');
    }

    protected function registerValidations(): void
    {
        $customClass = config('shield.auth.create_user.request_class');
        // Only bind if the user provided a custom request
        if ($customClass && $customClass !== ShieldCreateUserRequest::class) {
            $this->app->bind(ShieldCreateUserRequest::class, $customClass);
        }
        $loginClass = config('shield.auth.login.request_class');

        if ($loginClass && $loginClass !== ShieldLoginRequest::class) {
            $this->app->bind(ShieldLoginRequest::class, $loginClass);
        }
    }

    protected function registerServices(): void
    {
        // Register Social Auth Service
        $this->app->singleton(SocialAuthService::class, function ($app) {
            return new SocialAuthService;
        });

        // Register Auth Service Factory
        $this->app->singleton('shield.auth', function ($app) {
            return AuthServiceFactory::make();
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

        // Shield middleware
        $router->aliasMiddleware('shield.log', ShieldLog::class);
        $router->aliasMiddleware('privilege', EnsureShieldPrivilege::class);
        $router->aliasMiddleware('privileges', EnsureAnyShieldPrivilege::class);
        $router->aliasMiddleware('role', EnsureShieldRole::class);
        $router->aliasMiddleware('roles', EnsureAnyShieldRole::class);

        $authDriver = config('shield.auth_driver', 'sanctum');
        // Sanctum/Passport ability middleware
        if ($authDriver === 'sanctum') {
            if (! array_key_exists('ability', $router->getMiddleware())) {
                $router->aliasMiddleware('ability', CheckForAnyAbility::class);
            }

            if (! array_key_exists('abilities', $router->getMiddleware())) {
                $router->aliasMiddleware('abilities', CheckAbilities::class);
            }
        } elseif ($authDriver === 'passport') {
            if (! array_key_exists('abilities', $router->getMiddleware())) {
                $router->aliasMiddleware('scopes', \Laravel\Passport\Http\Middleware\CheckScopes::class);
            }
            if (! array_key_exists('ability', $router->getMiddleware())) {
                $router->aliasMiddleware('scope', \Laravel\Passport\Http\Middleware\CheckForAnyScope::class);
            }
        } elseif ($authDriver === 'jwt') {
            // JWT ability middleware
            if (! array_key_exists('ability', $router->getMiddleware())) {
                $router->aliasMiddleware('jwt.auth', JWTAuthenticate::class);
            }
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

    protected function registerAuthDriver(): void
    {
        $driver = config('shield.auth_driver', 'sanctum');

        // Configure guards based on driver
        if ($driver === 'jwt') {
            config(['auth.guards.api.driver' => 'jwt']);
        } elseif ($driver === 'passport') {
            config(['auth.guards.api.driver' => 'passport']);
        }
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
            PublishExceptionHandlerCommand::class,
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
            SwitchAuthDriverCommand::class,
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
