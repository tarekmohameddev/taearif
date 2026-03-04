<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add customer_id to users_property_requests, migrate data from pivot, then drop pivot.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            return;
        }

        if (!Schema::hasColumn('users_property_requests', 'customer_id')) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('user_id');
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('api_customers')
                    ->onDelete('set null');
                $table->index('customer_id');
            });
        }

        if (Schema::hasTable('api_customer_property_request')) {
            $pivotRows = DB::table('api_customer_property_request')
                ->orderBy('id')
                ->get(['property_request_id', 'customer_id']);
            foreach ($pivotRows as $row) {
                DB::table('users_property_requests')
                    ->where('id', $row->property_request_id)
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $row->customer_id]);
            }
            Schema::dropIfExists('api_customer_property_request');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users_property_requests') && Schema::hasColumn('users_property_requests', 'customer_id')) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropIndex(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }

        if (!Schema::hasTable('api_customer_property_request')) {
            Schema::create('api_customer_property_request', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('property_request_id');
                $table->timestamps();
                $table->foreign('customer_id')->references('id')->on('api_customers')->onDelete('cascade');
                $table->foreign('property_request_id')->references('id')->on('users_property_requests')->onDelete('cascade');
                $table->unique(['customer_id', 'property_request_id'], 'acpr_customer_request_unique');
            });
        }
    }
};
