<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('payment_gateways')->insert([
            'subtitle' => null,
            'title' => null,
            'details' => null,
            'name' => 'Arb',
            'type' => 'automatic',
            'information' => json_encode([
                "mode" => "test",
                "test_merchant_endpoint" => "https://securepayments.alrajhibank.com.sa/pg/payment/tranportal.htm",
                "live_merchant_endpoint" => "https://digitalpayments.alrajhibank.com.sa/pg/payment/tranportal.htm",
                "test_bank_hosted_endpoint" => "https://securepayments.alrajhibank.com.sa/pg/payment/hosted.htm",
                "live_bank_hosted_endpoint" => "https://digitalpayments.alrajhibank.com.sa/pg/payment/hosted.htm",
                "tranportal_id" => "bYKitXX1xo265N0",
                "tranportal_password" => "1vnl0z@!HE5F9@V",
                "resource_key" => "50020490190950020490190950020490",
                "currency_code" => "682",
                "redirect" => [
                    "success" => "/arb/success",
                    "fail" => "/arb/cancel"
                ]
            ]),
            'keyword' => 'arb',
            'status' => 1
        ]);
    }

    public function down()
    {
        DB::table('payment_gateways')->where('name', 'Arb')->delete();
    }
};
