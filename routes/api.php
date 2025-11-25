<?php

use NahidFerdous\Guardian\Http\Controllers\GuardianController;
use NahidFerdous\Guardian\Http\Controllers\PrivilegeController;
use NahidFerdous\Guardian\Http\Controllers\RoleController;
use NahidFerdous\Guardian\Http\Controllers\RolePrivilegeController;
use NahidFerdous\Guardian\Http\Controllers\UserController;
use NahidFerdous\Guardian\Http\Controllers\UserRoleController;
use NahidFerdous\Guardian\Http\Controllers\UserSuspensionController;
use Illuminate\Support\Facades\Route;

$guardMiddleware = 'auth:' . config('tyro.guard', 'sanctum');
$adminAbilities = 'ability:' . implode(',', config('tyro.abilities.admin', ['admin', 'super-admin']));
$userAbilities = 'ability:' . implode(',', config('tyro.abilities.user_update', ['admin', 'super-admin', 'user']));

Route::get('tyro', [GuardianController::class, 'tyro'])->name('tyro.info');
Route::get('tyro/version', [GuardianController::class, 'version'])->name('tyro.version');
Route::post('login', [UserController::class, 'login'])->name('tyro.login');
Route::post('users', [UserController::class, 'store'])->name('tyro.users.store');

Route::middleware([$guardMiddleware])->group(function () use ($adminAbilities, $userAbilities) {
    Route::get('me', [UserController::class, 'me'])->name('tyro.me');

    Route::middleware([$userAbilities])->group(function () {
        Route::match(['put', 'patch', 'post'], 'users/{user}', [UserController::class, 'update'])->name('tyro.users.update');
    });

    Route::middleware([$adminAbilities])->group(function () {
        Route::apiResource('users', UserController::class)->except(['store', 'update']);
        Route::post('users/{user}/suspend', [UserSuspensionController::class, 'store'])->name('tyro.users.suspend');
        Route::delete('users/{user}/suspend', [UserSuspensionController::class, 'destroy'])->name('tyro.users.unsuspend');
        Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
        Route::apiResource('users.roles', UserRoleController::class)->except(['create', 'edit', 'show', 'update']);
        Route::apiResource('privileges', PrivilegeController::class)->except(['create', 'edit']);
        Route::apiResource('roles.privileges', RolePrivilegeController::class)->only(['index', 'store', 'destroy']);
    });
});
