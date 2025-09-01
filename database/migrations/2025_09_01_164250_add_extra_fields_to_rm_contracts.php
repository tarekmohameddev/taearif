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
        Schema::table('rm_contracts', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('property_id')->nullable()->after('rental_id');
            $table->unsignedBigInteger('project_id')->nullable()->after('property_id');
            $table->string('property_name')->nullable()->after('project_id');
            $table->string('project_name')->nullable()->after('property_name');
            $table->decimal('water_fee_monthly', 12, 2)->default(0)->after('file_path');
            $table->enum('office_commission_type', ['percentage', 'amount'])->nullable()->after('water_fee_monthly');
            $table->decimal('office_commission_value', 12, 2)->nullable()->after('office_commission_type');
            $table->decimal('platform_fee', 12, 2)->default(0)->after('office_commission_value');
            $table->unsignedTinyInteger('grace_period_months')->default(0)->after('platform_fee');

            $table->index(['property_id']);
            $table->index(['project_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rm_contracts', function (Blueprint $table) {
            //
            $table->dropIndex(['property_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn([
                'property_id','project_id',
                'property_name','project_name',
                'water_fee_monthly',
                'office_commission_type','office_commission_value',
                'platform_fee',
                'grace_period_months',
            ]);

        });
    }
};
