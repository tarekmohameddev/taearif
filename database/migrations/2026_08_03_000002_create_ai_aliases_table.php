<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores location and property-type aliases discovered by the mining command.
 *
 * alias_type: 'city' | 'district' | 'property_type' | 'term'
 * alias: the raw text as customers write it (normalised, lower-case Arabic)
 * canonical: the authoritative name to resolve to
 * occurrence_count: how many times this alias was observed in the corpus
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias_type', 30)->index(); // city | district | property_type | term
            $table->string('alias', 200);
            $table->string('canonical', 200);
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamps();

            $table->unique(['alias_type', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_aliases');
    }
};
