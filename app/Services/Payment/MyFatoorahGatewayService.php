<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use Basel\MyFatoorah\MyFatoorah;
use Illuminate\Support\Facades\Config;

class MyFatoorahGatewayService
{
    public function getPaymentStatus(PaymentGateway $paymentMethod, string $paymentId): array
    {
        $paydata = $this->configure($paymentMethod);
        $myfatoorah = MyFatoorah::getInstance(($paydata['sandbox_status'] ?? 0) == 1);
        $result = $myfatoorah->getPaymentStatus('paymentId', $paymentId);

        return is_array($result) ? $result : [];
    }

    public function sendPayment(PaymentGateway $paymentMethod, array $params): array
    {
        $paydata = $this->configure(
            $paymentMethod,
            (string) ($params['currency'] ?? 'SAR'),
            (string) $params['success_url'],
            (string) $params['cancel_url']
        );
        $amount = $params['amount'];
        $customerReference = (string) $params['customer_reference'];
        $myfatoorah = MyFatoorah::getInstance(($paydata['sandbox_status'] ?? 0) == 1);

        $result = $myfatoorah->sendPayment(
            $params['customer_name'] ?? '',
            $amount,
            [
                'CustomerEmail' => $params['customer_email'] ?? '',
                'CustomerMobile' => ($paydata['sandbox_status'] ?? 0) == 1
                    ? '56562123544'
                    : ($params['customer_mobile'] ?? ''),
                'CustomerReference' => $customerReference,
                'UserDefinedField' => (string) ($params['userDefinedField'] ?? $customerReference),
                'Language' => $params['language'] ?? 'ar',
                'InvoiceItems' => [[
                    'ItemName' => $params['description'] ?? 'Credit Package Purchase',
                    'Quantity' => 1,
                    'UnitPrice' => $amount,
                ]],
            ]
        );

        return is_array($result) ? $result : [];
    }

    private function configure(
        PaymentGateway $paymentMethod,
        ?string $currency = null,
        ?string $successUrl = null,
        ?string $cancelUrl = null
    ): array {
        $paydata = $paymentMethod->convertAutoData();

        Config::set('myfatoorah.token', $paydata['token']);

        if ($currency !== null) {
            Config::set('myfatoorah.DisplayCurrencyIso', $currency);
        }
        if ($successUrl !== null) {
            Config::set('myfatoorah.CallBackUrl', $successUrl);
        }
        if ($cancelUrl !== null) {
            Config::set('myfatoorah.ErrorUrl', $cancelUrl);
        }

        return $paydata;
    }
}
