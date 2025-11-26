<?php

namespace NahidFerdous\Shield\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use NahidFerdous\Shield\Tests\TestCase;

class AdminLoginTest extends TestCase
{
    public function test_admin_login_succeeds(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@shield.project',
            'password' => 'shield',
        ]);

        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('error', 0)
            ->has('token')
            ->etc());
    }

    public function test_admin_login_fails_with_bad_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@shield.project',
            'password' => 'wrong-password',
        ]);

        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('error', 1)
            ->missing('token')
            ->has('message'));
    }

    public function test_admin_login_fails_when_suspended(): void
    {
        $userClass = config('shield.models.user');
        $admin = $userClass::where('email', 'admin@shield.project')->firstOrFail();
        $admin->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => 'Security hold',
        ])->save();

        $response = $this->postJson('/api/login', [
            'email' => 'admin@shield.project',
            'password' => 'shield',
        ]);

        $response->assertStatus(423)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('error', 1)
                ->where('message', 'user is suspended'));
    }
}
