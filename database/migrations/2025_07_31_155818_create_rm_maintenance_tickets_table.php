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
        Schema::create('rm_maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('rental_id')->index();

            $table->string('category', 50);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->index();
            $table->string('title', 150);
            $table->text('description');
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->enum('payer', ['landlord', 'tenant', 'shared'])->nullable();
            $table->tinyInteger('payer_share_percent')->nullable();
            $table->enum('status', ['open', 'in_progress', 'on_hold', 'resolved', 'cancelled'])->default('open')->index();
            $table->date('scheduled_date')->nullable();
            $table->unsignedBigInteger('assigned_to_vendor_id')->nullable();
            $table->smallInteger('attachments_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rm_maintenance_tickets');
    }
};
