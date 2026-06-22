<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_crm_relations', function (Blueprint $table) {
            $table->dropUnique('property_crm_relations_unique');
        });
    }

    public function down(): void
    {
        Schema::table('property_crm_relations', function (Blueprint $table) {
            $table->unique(['property_id', 'request_id', 'relation_type'], 'property_crm_relations_unique');
        });
    }
};
