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
        Schema::create('rm_rentals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->string('unit_label', 100)->nullable();

            $table->string('tenant_full_name', 150);
            $table->string('tenant_phone', 32)->index();
            $table->string('tenant_email', 150)->nullable();
            $table->string('tenant_job_title', 120)->nullable();
            $table->enum('tenant_social_status', ['single', 'married', 'divorced', 'widowed', 'other'])->nullable();
            $table->string('tenant_national_id', 20)->nullable();

            $table->decimal('base_rent_amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('SAR');
            $table->decimal('deposit_amount', 12, 2)->nullable();

            $table->date('move_in_date')->nullable();
            $table->enum('paying_plan', ['monthly', 'quarterly', 'semi_annual', 'annual'])->nullable();
            $table->smallInteger('rental_period_months')->nullable();

            $table->enum('status', ['draft', 'active', 'ended', 'cancelled'])->default('draft')->index();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
        Schema::dropIfExists('rm_rentals');
    }
};
