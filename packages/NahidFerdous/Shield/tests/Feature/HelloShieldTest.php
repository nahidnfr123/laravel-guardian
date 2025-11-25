<?php

namespace NahidFerdous\Shield\Tests\Feature;

use NahidFerdous\Shield\Tests\TestCase;

class HelloShieldTest extends TestCase
{
    public function test_hello_shield_endpoint_returns_message(): void
    {
        $response = $this->get('/api/shield');

        $response->assertStatus(200)->assertJsonStructure(['message']);
    }

    public function test_version_endpoint_returns_configured_version(): void
    {
        config(['shield.version' => '9.9.9']);

        $response = $this->get('/api/shield/version');

        $response->assertStatus(200)->assertJson(['version' => '9.9.9']);
    }
}
