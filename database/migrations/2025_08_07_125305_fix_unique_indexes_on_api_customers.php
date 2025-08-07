<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixUniqueIndexesOnApiCustomers extends Migration
{
    public function up(): void
    {
        Schema::table('api_customers', function (Blueprint $table) {

            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('api_customers');

            if (array_key_exists('api_customers_phone_number_unique', $indexes)) {
                $table->dropUnique('api_customers_phone_number_unique');
            }

            if (array_key_exists('api_customers_email_unique', $indexes)) {
                $table->dropUnique('api_customers_email_unique');
            }

            $table->unique(['user_id', 'phone_number'], 'unique_user_phone');
            $table->unique(['user_id', 'email'], 'unique_user_email');
        });
    }

    public function down(): void
    {
        Schema::table('api_customers', function (Blueprint $table) {
            $table->dropUnique('unique_user_phone');
            $table->dropUnique('unique_user_email');

            $table->unique('phone_number', 'api_customers_phone_number_unique');
            $table->unique('email', 'api_customers_email_unique');
        });
    }
}
