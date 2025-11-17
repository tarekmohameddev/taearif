<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('domain_renewal_pricings')) {
            Schema::create('domain_renewal_pricings', function (Blueprint $table) {
                $table->id();
                $table->integer('custom_domain_id')->nullable(); // will be corrected below
                $table->string('registrar', 100)->nullable();
                $table->string('period_key', 50);
                $table->string('label');
                $table->unsignedInteger('years');
                $table->decimal('price', 10, 2);
                $table->string('currency', 10)->default('SAR');
                $table->boolean('active')->default(true);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->timestamps();
            });
        }

        // Align custom_domain_id type to user_custom_domains.id
        $col = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_custom_domains'
              AND COLUMN_NAME = 'id'
        ");
        if (!$col) {
            throw new \RuntimeException('Could not inspect user_custom_domains.id type');
        }
        $isBigInt   = stripos($col->COLUMN_TYPE, 'bigint') !== false;
        $isUnsigned = stripos($col->COLUMN_TYPE, 'unsigned') !== false;

        DB::statement(
            'ALTER TABLE `domain_renewal_pricings` MODIFY `custom_domain_id` '
            . ($isBigInt ? 'BIGINT' : 'INT')
            . ($isUnsigned ? ' UNSIGNED' : '')
            . ' NULL'
        );

        // Add FK (ignore if already exists)
        try {
            Schema::table('domain_renewal_pricings', function (Blueprint $table) {
                $table->foreign('custom_domain_id')
                    ->references('id')
                    ->on('user_custom_domains')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // FK already exists
        }

        // Ensure indexes
        try { Schema::table('domain_renewal_pricings', fn (Blueprint $t) => $t->index(['custom_domain_id','period_key','active'], 'idx_domain_period')); } catch (\Throwable $e) {}
        try { Schema::table('domain_renewal_pricings', fn (Blueprint $t) => $t->index(['registrar','period_key','active'], 'idx_registrar_period')); } catch (\Throwable $e) {}
        try { Schema::table('domain_renewal_pricings', fn (Blueprint $t) => $t->index(['starts_at','ends_at'], 'idx_dates')); } catch (\Throwable $e) {}
    }

    public function down()
    {
        Schema::dropIfExists('domain_renewal_pricings');
    }
};