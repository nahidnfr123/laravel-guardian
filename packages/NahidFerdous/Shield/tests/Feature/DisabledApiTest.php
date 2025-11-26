<?php

namespace NahidFerdous\Shield\Tests\Feature;

use NahidFerdous\Shield\Tests\TestCase;

class DisabledApiTest extends TestCase
{
    protected bool $disableShieldApi = true;

    public function test_shield_routes_are_not_registered_when_disabled(): void
    {
        $this->get('/api/shield')->assertNotFound();
        $this->get('/api/shield/version')->assertNotFound();
    }
}
