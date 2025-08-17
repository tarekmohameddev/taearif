<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            // خلي التصنيف اختياري
            $table->string('property_type')->nullable()->change();

            // خلي الميزانية اختياري
            $table->decimal('budget_from', 15, 2)->nullable()->change();
            $table->decimal('budget_to', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            // رجّعهم إجباريين (لو حبيت ترول باك)
            $table->string('property_type')->nullable(false)->change();
            $table->decimal('budget_from', 15, 2)->nullable(false)->change();
            $table->decimal('budget_to', 15, 2)->nullable(false)->change();
        });
    }
};

