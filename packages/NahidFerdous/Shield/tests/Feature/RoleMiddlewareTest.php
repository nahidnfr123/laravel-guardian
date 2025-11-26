<?php

namespace NahidFerdous\Shield\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Tests\Fixtures\User;
use NahidFerdous\Shield\Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_role_middleware_requires_every_listed_role(): void
    {
        Route::middleware(['auth:sanctum', 'role:admin,super-admin'])
            ->get('/shield/middleware/role-all', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'admin')->first()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/shield/middleware/role-all')->assertForbidden();

        $user->roles()->sync([
            Role::where('slug', 'admin')->first()->id,
            Role::where('slug', 'super-admin')->first()->id,
        ]);

        $this->getJson('/shield/middleware/role-all')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_roles_middleware_passes_when_any_role_matches(): void
    {
        Route::middleware(['auth:sanctum', 'roles:admin,super-admin'])
            ->get('/shield/middleware/roles-any', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'admin')->first()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/shield/middleware/roles-any')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_roles_middleware_blocks_when_no_roles_match(): void
    {
        Route::middleware(['auth:sanctum', 'roles:admin,super-admin'])
            ->get('/shield/middleware/roles-block', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/shield/middleware/roles-block')->assertForbidden();
    }
}
