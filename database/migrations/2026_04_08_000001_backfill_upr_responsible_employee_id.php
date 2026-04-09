<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill request-level assignment from customer assignment where possible.
        // Safe/non-destructive: only fills NULL values.

        // 1) Direct link: upr.customer_id -> api_customers.id
        DB::statement("
            UPDATE users_property_requests upr
            INNER JOIN api_customers ac
                ON ac.id = upr.customer_id
                AND ac.user_id = upr.user_id
                AND ac.responsible_employee_id IS NOT NULL
            SET upr.responsible_employee_id = ac.responsible_employee_id,
                upr.updated_at = NOW()
            WHERE upr.responsible_employee_id IS NULL
        ");

        // 2) Phone match: upr.phone -> api_customers.phone_number (same tenant)
        DB::statement("
            UPDATE users_property_requests upr
            INNER JOIN api_customers ac
                ON ac.user_id = upr.user_id
                AND ac.phone_number = upr.phone
                AND ac.responsible_employee_id IS NOT NULL
            SET upr.responsible_employee_id = ac.responsible_employee_id,
                upr.updated_at = NOW()
            WHERE upr.responsible_employee_id IS NULL
              AND upr.customer_id IS NULL
        ");
    }

    public function down(): void
    {
        // Intentionally no-op: cannot safely revert without losing legitimate assignments.
    }
};

