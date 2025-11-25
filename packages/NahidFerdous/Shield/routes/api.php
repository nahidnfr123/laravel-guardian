<?php

use Illuminate\Support\Facades\Route;
use NahidFerdous\Shield\Http\Controllers\PrivilegeController;
use NahidFerdous\Shield\Http\Controllers\RoleController;
use NahidFerdous\Shield\Http\Controllers\RolePrivilegeController;
use NahidFerdous\Shield\Http\Controllers\ShieldController;
use NahidFerdous\Shield\Http\Controllers\UserController;
use NahidFerdous\Shield\Http\Controllers\UserRoleController;
use NahidFerdous\Shield\Http\Controllers\UserSuspensionController;

$guardMiddleware = 'auth:'.config('shield.guard', 'sanctum');
$adminAbilities = 'ability:'.implode(',', config('shield.abilities.admin', ['admin', 'super-admin']));
$userAbilities = 'ability:'.implode(',', config('shield.abilities.user_update', ['admin', 'super-admin', 'user']));

Route::get('shield', [ShieldController::class, 'shield'])->name('shield.info');
Route::get('shield/version', [ShieldController::class, 'version'])->name('shield.version');
Route::post('login', [UserController::class, 'login'])->name('shield.login');
Route::post('users', [UserController::class, 'store'])->name('shield.users.store');

Route::middleware([$guardMiddleware])->group(function () use ($adminAbilities, $userAbilities) {
    Route::get('me', [UserController::class, 'me'])->name('shield.me');

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
