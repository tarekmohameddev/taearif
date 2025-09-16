<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->enum('whatsapp_service', ['meta_cloud', 'evolution_api'])->nullable();
            
            // Meta Cloud API fields
            $table->text('meta_access_token')->nullable();
            $table->string('meta_phone_number_id')->nullable();
            $table->string('meta_business_account_id')->nullable();
            $table->string('meta_template_name')->nullable();
            $table->string('meta_template_language')->nullable();
            
            // Evolution API fields
            $table->string('evolution_api_url')->nullable();
            $table->text('evolution_api_key')->nullable();
            $table->string('evolution_instance_name')->nullable();
            $table->string('evolution_phone_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_service',
                'meta_access_token',
                'meta_phone_number_id',
                'meta_business_account_id',
                'meta_template_name',
                'meta_template_language',
                'evolution_api_url',
                'evolution_api_key',
                'evolution_instance_name',
                'evolution_phone_number'
            ]);
        });
    }
};
