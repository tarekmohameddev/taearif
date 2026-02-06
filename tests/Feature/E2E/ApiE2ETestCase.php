<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Base for API E2E tests.
 *
 * E2E testing strategy lock: NEVER use migrations. Assume schema exists; never run migrations.
 *
 * - Uses DatabaseTransactions for isolation (never RefreshDatabase).
 * - Do NOT run migrate:fresh for E2E; schema comes from dump only.
 * - Restore DB from the_test_db/taearif_testing.sql before running E2E tests.
 */
abstract class ApiE2ETestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * Fake reCAPTCHA API so login/register validation passes in tests.
     * Call before POST /api/login (register uses TEST_BYPASS_TOKEN).
     */
    protected function fakeRecaptcha(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/*' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
        ]);
    }
}
