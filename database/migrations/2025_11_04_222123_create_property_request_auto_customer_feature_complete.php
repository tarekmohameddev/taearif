<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Complete property request auto-customer feature
     *
     * @return void
     */
    public function up()
    {
        // 1. Create settings table
        Schema::create('property_request_auto_customer_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('auto_create_customer')->default(false);
            $table->unsignedBigInteger('default_stage_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('default_stage_id')->references('id')->on('users_api_customers_stages')->onDelete('set null');

            // Performance: Index for common query pattern
            $table->index(['user_id', 'auto_create_customer'], 'idx_enabled_lookup');
        });

        // 2. Add api_customers table
        Schema::table('api_customers', function (Blueprint $table) {
            // Add unique constraint to prevent race conditions
            if (!$this->hasConstraint('api_customers', 'unique_phone_per_tenant')) {
                $table->unique(['user_id', 'phone_number'], 'unique_phone_per_tenant');
            }

            // Add index on phone_number for faster lookups
            if (!$this->hasIndex('api_customers', 'api_customers_phone_number_index')) {
                $table->index('phone_number');
            }

            // Add source tracking columns if they don't exist
            if (!Schema::hasColumn('api_customers', 'source')) {
                $table->string('source', 50)->nullable()->after('password')
                    ->comment('Source: manual, property_request, whatsapp, import, etc.');
            }

            if (!Schema::hasColumn('api_customers', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source')
                    ->comment('ID of the source record');
                $table->index('source_id');
            }

            // Link to property request
            if (!Schema::hasColumn('api_customers', 'property_request_id')) {
                $table->unsignedBigInteger('property_request_id')->nullable()->after('user_id');
                $table->foreign('property_request_id')
                    ->references('id')
                    ->on('users_property_requests')
                    ->nullOnDelete();
                $table->index('property_request_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove api_customers
        Schema::table('api_customers', function (Blueprint $table) {
            // Drop foreign keys first
            if ($this->hasForeignKey('api_customers', 'api_customers_property_request_id_foreign')) {
                $table->dropForeign(['property_request_id']);
            }

            // Drop indexes
            if ($this->hasIndex('api_customers', 'api_customers_property_request_id_index')) {
                $table->dropIndex(['property_request_id']);
            }
            if ($this->hasIndex('api_customers', 'api_customers_source_id_index')) {
                $table->dropIndex(['source_id']);
            }
            if ($this->hasIndex('api_customers', 'api_customers_phone_number_index')) {
                $table->dropIndex(['phone_number']);
            }
            if ($this->hasConstraint('api_customers', 'unique_phone_per_tenant')) {
                $table->dropUnique('unique_phone_per_tenant');
            }

            // Drop columns
            if (Schema::hasColumn('api_customers', 'property_request_id')) {
                $table->dropColumn('property_request_id');
            }
            if (Schema::hasColumn('api_customers', 'source')) {
                $table->dropColumn(['source', 'source_id']);
            }
        });

        // Drop settings table
        Schema::dropIfExists('property_request_auto_customer_settings');
    }

    /**
     * Check if index exists
     */
    private function hasIndex(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $doctrineSchemaManager->listTableIndexes($table);
            return isset($indexes[$index]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if constraint exists
     */
    private function hasConstraint(string $table, string $constraint): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $doctrineSchemaManager->listTableIndexes($table);
            return isset($indexes[$constraint]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if foreign key exists
     */
    private function hasForeignKey(string $table, string $foreign): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $foreignKeys = $doctrineSchemaManager->listTableForeignKeys($table);
            foreach ($foreignKeys as $fk) {
                if ($fk->getName() === $foreign) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};
