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
        Schema::table('email_templates', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('email_templates', 'name')) {
                $table->string('name')->unique()->after('id');
            }
            if (!Schema::hasColumn('email_templates', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('email_templates', 'subject')) {
                $table->text('subject')->after('description');
            }
            if (!Schema::hasColumn('email_templates', 'content')) {
                $table->longText('content')->after('subject');
            }
            if (!Schema::hasColumn('email_templates', 'type')) {
                $table->string('type')->after('content');
            }
            if (!Schema::hasColumn('email_templates', 'language')) {
                $table->string('language')->default('ar')->after('type');
            }
            if (!Schema::hasColumn('email_templates', 'variables')) {
                $table->json('variables')->nullable()->after('language');
            }
            if (!Schema::hasColumn('email_templates', 'status')) {
                $table->boolean('status')->default(true)->after('variables');
            }
            if (!Schema::hasColumn('email_templates', 'character_count')) {
                $table->integer('character_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('email_templates', 'created_at')) {
                $table->timestamps();
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
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'description', 'subject', 'content', 
                'type', 'language', 'variables', 'status', 
                'character_count', 'created_at', 'updated_at'
            ]);
        });
    }
};
