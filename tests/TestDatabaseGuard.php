<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use RuntimeException;

final class TestDatabaseGuard
{
    public const DATABASE = 'firma_finder_testing';

    /**
     * Stop the test run before RefreshDatabase can wipe a database
     * other than the dedicated test database.
     */
    public static function ensure(Repository $config): void
    {
        $connection = $config->get('database.default');
        $database = $config->get("database.connections.{$connection}.database");

        if ($database !== self::DATABASE) {
            throw new RuntimeException(
                'Tests must run against '.self::DATABASE
                .", but the [{$connection}] connection uses [{$database}]."
            );
        }
    }
}
