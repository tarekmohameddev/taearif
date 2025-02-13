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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('subject');
            $table->decimal('contract_value', 15, 2);
            $table->enum('contract_type', ['Standard', 'Contracts under Seal', 'Lease Agreement', 'Other'])->default('Standard');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->string('contract_status')->default('draft');

            // Signed contract fields
            $table->boolean('is_signed')->default(false);
            $table->string('signed_name')->nullable();
            $table->timestamp('signed_date')->nullable();
            $table->string('signed_ip')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};
