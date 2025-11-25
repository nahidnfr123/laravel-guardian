<?php

namespace NahidFerdous\Guardian\Tests\Feature;

use NahidFerdous\Guardian\Tests\TestCase;

class DisabledApiTest extends TestCase {
    protected bool $disableTyroApi = true;

    public function test_tyro_routes_are_not_registered_when_disabled(): void {
        $this->get('/api/tyro')->assertNotFound();
        $this->get('/api/tyro/version')->assertNotFound();
    }
}
