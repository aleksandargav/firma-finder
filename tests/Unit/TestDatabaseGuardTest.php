<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\TestDatabaseGuard;

class TestDatabaseGuardTest extends TestCase
{
    public function test_rejects_the_development_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('[mysql] connection uses [firma_finder]');

        TestDatabaseGuard::ensure($this->config('mysql', 'firma_finder'));
    }

    public function test_rejects_an_in_memory_sqlite_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('[sqlite] connection uses [:memory:]');

        TestDatabaseGuard::ensure($this->config('sqlite', ':memory:'));
    }

    public function test_accepts_the_test_database(): void
    {
        TestDatabaseGuard::ensure($this->config('mysql', 'firma_finder_testing'));

        $this->addToAssertionCount(1);
    }

    private function config(string $connection, string $database): Repository
    {
        return new Repository([
            'database' => [
                'default' => $connection,
                'connections' => [
                    $connection => ['database' => $database],
                ],
            ],
        ]);
    }
}
