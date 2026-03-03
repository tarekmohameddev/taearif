<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pivot table: many-to-many between api_customers and users_property_requests.
     */
    public function up(): void
    {
        if (!Schema::hasTable('api_customer_property_request')) {
            Schema::create('api_customer_property_request', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('property_request_id');
                $table->timestamps();

                $table->foreign('customer_id')
                    ->references('id')
                    ->on('api_customers')
                    ->onDelete('cascade');
                $table->foreign('property_request_id')
                    ->references('id')
                    ->on('users_property_requests')
                    ->onDelete('cascade');

                $table->unique(['customer_id', 'property_request_id'], 'acpr_customer_request_unique');
            });
        } else {
            // Table existed from a previous partial run; ensure unique index exists
            $indexExists = DB::select("SHOW INDEX FROM api_customer_property_request WHERE Key_name = 'acpr_customer_request_unique'");
            if (empty($indexExists)) {
                Schema::table('api_customer_property_request', function (Blueprint $table) {
                    $table->unique(['customer_id', 'property_request_id'], 'acpr_customer_request_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_customer_property_request');
    }
};
