<?php

use App\Models\PaymentGateway;
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
        Schema::table('payment_gateways', function (Blueprint $table) {
            $data = PaymentGateway::where('keyword', 'phonepe')->first();
            if (empty($data)) {
                $phonepe = new PaymentGateway();
                $phonepe->status = 1;
                $phonepe->name = 'PhonePe';
                $phonepe->keyword = 'phonepe';
                $phonepe->type = 'automatic';

                $information = [];
                $information['merchant_id'] = 'PGTESTPAYUAT';
                $information['salt_key'] = '099eb0cd-02cf-4e2a-8aca-3e6c6aff0399';
                $information['salt_index'] = 1;
                $information['sandbox_check'] = 1;
                $information['text'] = "Pay via your PhonePe account.";

                $phonepe->information = json_encode($information);
                $phonepe->save();
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
        Schema::table('payment_gateways', function (Blueprint $table) {
            $data = PaymentGateway::where('keyword', 'phonepe')->first();
            if (!empty($data)) {
                $data->delete();
            }
        });
    }
};
