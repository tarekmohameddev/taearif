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
            /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            --------- Myfatoorah -----------------
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
            $myfatoorah = PaymentGateway::where('keyword', 'myfatoorah')->first();
            if (empty($myfatoorah)) {
                $information = [
                    'sandbox_status' => null,
                    'token' => null
                ];
                $myfatoorah = [
                    'name' => 'Myfatoorah',
                    'keyword' => 'myfatoorah',
                    'type' => 'automatic',
                    'information' => json_encode($information, true),
                    'status' => 0
                ];
                PaymentGateway::create($myfatoorah);
            }

            /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            --------- Yoco -----------------
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
            $yoco = PaymentGateway::where('keyword', 'yoco')->first();
            if (empty($yoco)) {
                $information = [
                    'secret_key' => null
                ];
                $yoco = [
                    'name' => 'Yoco',
                    'keyword' => 'yoco',
                    'type' => 'automatic',
                    'information' => json_encode($information, true),
                    'status' => 0
                ];
                PaymentGateway::create($yoco);
            }

            /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            --------- toyyibpay -----------------
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
            $toyyibpay = PaymentGateway::where('keyword', 'toyyibpay')->first();
            if (empty($toyyibpay)) {
                $information = [
                    'sandbox_status' => null,
                    'secret_key' => null,
                    'category_code' => null
                ];
                $toyyibpay = [
                    'name' => 'Toyyibpay',
                    'keyword' => 'toyyibpay',
                    'type' => 'automatic',
                    'information' => json_encode($information, true),
                    'status' => 0
                ];
                PaymentGateway::create($toyyibpay);
            }

            /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            --------- paytabs -----------------
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
            $paytabs = PaymentGateway::where('keyword', 'paytabs')->first();
            if (empty($paytabs)) {
                $information = [
                    'profile_id' => null,
                    'server_key' => null,
                    'api_endpoint' => null,
                    'country' => null
                ];
                $paytabs = [
                    'name' => 'Paytabs',
                    'keyword' => 'paytabs',
                    'type' => 'automatic',
                    'information' => json_encode($information, true),
                    'status' => 0
                ];
                PaymentGateway::create($paytabs);
            }

            /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            --------- iyzico -----------------
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
            $iyzico = PaymentGateway::where('keyword', 'iyzico')->first();
            if (empty($iyzico)) {
                $information = [
                    'api_key' => null,
                    'secret_key' => null,
                    'sandbox_status' => null
                ];
                $iyzico = [
                    'name' => 'Iyzico',
                    'keyword' => 'iyzico',
                    'type' => 'automatic',
                    'information' => json_encode($information, true),
                    'status' => 0
                ];
                PaymentGateway::create($iyzico);
            }

            /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            --------- midtrans -----------------
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
            $midtrans = PaymentGateway::where('keyword', 'midtrans')->first();
            if (empty($midtrans)) {
                $information = [
                    'server_key' => null,
                    'is_production' => null
                ];
                $midtrans = [
                    'name' => 'Midtrans',
                    'keyword' => 'midtrans',
                    'type' => 'automatic',
                    'information' => json_encode($information, true),
                    'status' => 0
                ];
                PaymentGateway::create($midtrans);
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
            $myfatoorah = PaymentGateway::where('keyword', 'myfatoorah')->first();
            if (!empty($myfatoorah)) {
                $myfatoorah->delete();
            }
            $yoco = PaymentGateway::where('keyword', 'yoco')->first();
            if (!empty($yoco)) {
                $yoco->delete();
            }
            $toyyibpay = PaymentGateway::where('keyword', 'toyyibpay')->first();
            if (!empty($toyyibpay)) {
                $toyyibpay->delete();
            }
            $paytabs = PaymentGateway::where('keyword', 'paytabs')->first();
            if (!empty($paytabs)) {
                $paytabs->delete();
            }
            $iyzico = PaymentGateway::where('keyword', 'iyzico')->first();
            if (!empty($iyzico)) {
                $iyzico->delete();
            }
            $midtrans = PaymentGateway::where('keyword', 'midtrans')->first();
            if (!empty($midtrans)) {
                $midtrans->delete();
            }
        });
    }
};
