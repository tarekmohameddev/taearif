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
            $data = PaymentGateway::where('keyword', 'perfect_money')->first();
            if (empty($data)) {
                $information = [
                    'perfect_money_wallet_id' => null
                ];
                $data = [
                    'name' => 'Perfect Money',
                    'keyword' => 'perfect_money',
                    'type' => 'automatic',
                    'information' => json_encode($information, true),
                    'status' => 0
                ];
                PaymentGateway::create($data);
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
        $data = PaymentGateway::where('keyword', 'perfect_money')->first();
        if ($data) {
            $data->delete();
        }
    }
};
