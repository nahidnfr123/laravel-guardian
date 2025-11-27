<?php

use Illuminate\Support\Facades\Route;
use NahidFerdous\Shield\Http\Controllers\AuthController;
use NahidFerdous\Shield\Http\Controllers\PrivilegeController;
use NahidFerdous\Shield\Http\Controllers\RoleController;
use NahidFerdous\Shield\Http\Controllers\RolePrivilegeController;
use NahidFerdous\Shield\Http\Controllers\ShieldController;
use NahidFerdous\Shield\Http\Controllers\SocialAuthController;
use NahidFerdous\Shield\Http\Controllers\UserController;
use NahidFerdous\Shield\Http\Controllers\UserRoleController;
use NahidFerdous\Shield\Http\Controllers\UserSuspensionController;

$driver = config('shield.auth_driver', 'sanctum');
/**
 * Get authentication middleware based on a configured driver
 */
$getAuthMiddleware = match ($driver) {
    // 'sanctum' => 'auth:sanctum',
    'passport', 'jwt' => 'auth:api',
    default => 'auth:sanctum',
};

/*
 * Get ability middleware based on a configured driver
 */
$getAbilityMiddleware = match ($driver) {
    // 'sanctum' => 'ability',
    'passport' => 'scopes', // or 'scope'
    'jwt' => 'roles', // custom, explained below
    default => 'ability' // or 'abilities'
};

$adminAbilities = $getAbilityMiddleware.':'.implode(',', config('shield.abilities.admin', ['admin', 'super-admin']));
$userAbilities = $getAbilityMiddleware.':'.implode(',', config('shield.abilities.user_update', ['admin', 'super-admin', 'user']));
$throttleAttempts = config('shield.auth.throttle_attempts', 6);

// Route::get('shield', [ShieldController::class, 'shield'])->name('shield.info');
// Route::get('shield/version', [ShieldController::class, 'version'])->name('shield.version');

// Authentication routes with throttling
Route::middleware(["throttle:{$throttleAttempts},1"])->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('shield.login');
    Route::post('register', [UserController::class, 'store'])->name('shield.users.store');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('shield.forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('shield.reset-password');
});

// Email verification route (only if check_verified is enabled)
if (config('shield.auth.check_verified', false)) {
    Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail'])
        ->middleware(["throttle:{$throttleAttempts},1"])
        ->name('shield.verify-email');
    Route::middleware(["throttle:{$throttleAttempts},1"])->group(function () {
        Route::post('resend-email-verification-link', [AuthController::class, 'resendEmailVerificationLink'])->name('shield.resend-email-verification-link');
    });
}

// Social authentication routes
if (config('shield.social.enabled', false)) {
    Route::prefix('auth')->group(function () {
        Route::get('/providers', [SocialAuthController::class, 'providers'])->name('social.providers');
        Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
        Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
    });
}

Route::middleware([$getAuthMiddleware])->group(function () use ($adminAbilities, $userAbilities) {
    Route::get('me', [AuthController::class, 'me'])->name('shield.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');

    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('shield.change.password');
    });

    Route::middleware([$userAbilities])->group(function () {
        Route::match(['put', 'patch', 'post'], 'users/{user}', [UserController::class, 'update'])->name('shield.users.update');
    });

    Route::middleware([$adminAbilities])->group(function () {
        Route::apiResource('users', UserController::class)->except(['store', 'update']);
        Route::post('users/{user}/suspend', [UserSuspensionController::class, 'store'])->name('shield.users.suspend');
        Route::delete('users/{user}/suspend', [UserSuspensionController::class, 'destroy'])->name('shield.users.unsuspend');
        Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
        Route::apiResource('users.roles', UserRoleController::class)->except(['create', 'edit', 'show', 'update']);
        Route::apiResource('privileges', PrivilegeController::class)->except(['create', 'edit']);
        Route::apiResource('roles.privileges', RolePrivilegeController::class)->only(['index', 'store', 'destroy']);
    });
});
