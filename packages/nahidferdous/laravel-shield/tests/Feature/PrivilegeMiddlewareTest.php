<?php

namespace NahidFerdous\Shield\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Tests\Fixtures\User;
use NahidFerdous\Shield\Tests\TestCase;

class PrivilegeMiddlewareTest extends TestCase
{
    public function test_privilege_middleware_blocks_when_privilege_missing(): void
    {
        Route::middleware(['auth:sanctum', 'privilege:report.generate'])
            ->get('/shield/middleware/privilege-block', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'user')->first()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/shield/middleware/privilege-block')->assertForbidden();
    }

    public function test_privilege_middleware_allows_user_with_required_privilege(): void
    {
        Route::middleware(['auth:sanctum', 'privilege:report.generate'])
            ->get('/shield/middleware/privilege-allow', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'admin')->first()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/shield/middleware/privilege-allow')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_privileges_middleware_allows_when_any_privilege_matches(): void
    {
        Route::middleware(['auth:sanctum', 'privileges:billing.view,users.manage'])
            ->get('/shield/middleware/privileges-any', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'user')->first()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/shield/middleware/privileges-any')->assertOk();
    }

    public function test_privileges_middleware_blocks_when_no_privileges_match(): void
    {
        Route::middleware(['auth:sanctum', 'privileges:users.manage,roles.manage'])
            ->get('/shield/middleware/privileges-block', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/shield/middleware/privileges-block')->assertForbidden();
    }
}
