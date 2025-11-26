<?php

namespace NahidFerdous\Shield\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use NahidFerdous\Shield\Tests\TestCase;

class DisabledCommandsTest extends TestCase
{
    protected bool $disableShieldCommands = true;

    public function test_shield_commands_are_not_registered_when_disabled(): void
    {
        $this->assertArrayNotHasKey('shield:about', Artisan::all());
        $this->assertArrayNotHasKey('shield:doc', Artisan::all());
        $this->assertArrayNotHasKey('shield:quick-token', Artisan::all());
        $this->assertArrayNotHasKey('shield:install', Artisan::all());
        $this->assertArrayNotHasKey('shield:prepare-user-model', Artisan::all());
    }
}
