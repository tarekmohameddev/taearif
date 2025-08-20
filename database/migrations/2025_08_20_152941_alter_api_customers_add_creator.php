<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('api_customers', function (Blueprint $table) {
      $table->enum('created_by_type', ['user','employee','system'])->default('system')->after('remember_token');
      $table->unsignedBigInteger('created_by_id')->nullable()->after('created_by_type');
      $table->index(['created_by_type','created_by_id']);
    });
  }
  public function down(): void {
    Schema::table('api_customers', function (Blueprint $table) {
      $table->dropIndex(['created_by_type','created_by_id']);
      $table->dropColumn(['created_by_type','created_by_id']);
    });
  }
};
