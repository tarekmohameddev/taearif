<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $uses = class_uses_recursive(static::class) ?: [];
        $usesRefreshDatabase = in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, $uses, true);

        if (! $usesRefreshDatabase) {
            return;
        }

        $dbName = (string) config('database.connections.' . config('database.default') . '.database');
        $allowed = (string) env('ALLOW_REAL_DB_TESTS', '0') === '1';

        if ($allowed) {
            return;
        }

        throw new \RuntimeException(
            "This test uses RefreshDatabase but ALLOW_REAL_DB_TESTS is not enabled. " .
            "Refusing to run because it may wipe a real database. Current DB: '{$dbName}'."
        );
    }
}
