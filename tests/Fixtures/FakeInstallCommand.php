<?php

namespace NahidFerdous\Guardian\Tests\Fixtures;

use NahidFerdous\Guardian\Console\Commands\InstallCommand;

class FakeInstallCommand extends InstallCommand
{
    public static array $recorded = [];

    protected function runRequiredCommand(string $command, array $arguments = []): bool
    {
        self::$recorded[] = [
            'command' => $command,
            'arguments' => $arguments,
        ];

        return true;
    }

    public static function reset(): void
    {
        self::$recorded = [];
    }
}
