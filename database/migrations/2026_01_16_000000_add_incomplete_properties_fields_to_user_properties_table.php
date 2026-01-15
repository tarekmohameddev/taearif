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
        Schema::table('user_properties', function (Blueprint $table) {
            $table->enum('completion_status', ['complete', 'incomplete', 'pending_review'])
                  ->default('complete')
                  ->after('status')
                  ->comment('Property completion status for imports');
            
            $table->json('missing_fields')->nullable()
                  ->after('completion_status')
                  ->comment('Array of missing required field names');
            
            $table->json('validation_errors')->nullable()
                  ->after('missing_fields')
                  ->comment('Array of validation errors');
            
            $table->string('import_batch_id')->nullable()
                  ->after('validation_errors')
                  ->index()
                  ->comment('Groups related incomplete properties from same import');
            
            $table->timestamp('completed_at')->nullable()
                  ->after('import_batch_id')
                  ->comment('When property was completed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropColumn([
                'completion_status',
                'missing_fields',
                'validation_errors',
                'import_batch_id',
                'completed_at'
            ]);
        });
    }
};
