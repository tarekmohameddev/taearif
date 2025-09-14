<?php

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
| Routes for payment gateway success, cancel, and notification handling
|
*/

// Membership Payment Gateways
Route::prefix('membership')->group(function () {
    Route::get('paypal/success', "Payment\PaypalController@successPayment")->name('membership.paypal.success');
    Route::get('paypal/cancel', "Payment\PaypalController@cancelPayment")->name('membership.paypal.cancel');
    Route::get('stripe/cancel', "Payment\StripeController@cancelPayment")->name('membership.stripe.cancel');
    Route::post('paytm/payment-status', "Payment\PaytmController@paymentStatus")->name('membership.paytm.status');
    Route::get('paystack/success', 'Payment\PaystackController@successPayment')->name('membership.paystack.success');
    Route::post('mercadopago/success', 'Payment\MercadopagoController@successPayment')->name('membership.mercadopago.success');
    Route::post('razorpay/success', 'Payment\RazorpayController@successPayment')->name('membership.razorpay.success');
    Route::post('razorpay/cancel', 'Payment\RazorpayController@cancelPayment')->name('membership.razorpay.cancel');
    Route::get('instamojo/success', 'Payment\InstamojoController@successPayment')->name('membership.instamojo.success');
    Route::post('instamojo/cancel', 'Payment\InstamojoController@cancelPayment')->name('membership.instamojo.cancel');
    Route::post('flutterwave/success', 'Payment\FlutterWaveController@successPayment')->name('membership.flutterwave.success');
    Route::post('flutterwave/cancel', 'Payment\FlutterWaveController@cancelPayment')->name('membership.flutterwave.cancel');
    Route::get('/mollie/success', 'Payment\MollieController@successPayment')->name('membership.mollie.success');
    Route::post('mollie/cancel', 'Payment\MollieController@cancelPayment')->name('membership.mollie.cancel');
    Route::get('anet/cancel', 'Payment\AuthorizenetController@cancelPayment')->name('membership.anet.cancel');
    Route::post('/phonepe/success', 'Payment\PhonePeController@successPayment')->name('membership.phonepe.success');
    Route::post('phonepe/cancel', 'Payment\PhonePeController@cancelPayment')->name('membership.phonepe.cancel');
    Route::get('/perfect_money/success', 'Payment\PerfectMoneyController@successPayment')->name('membership.perfect_money.success');
    Route::get('perfect_money/cancel', 'Payment\PerfectMoneyController@cancelPayment')->name('membership.perfect_money.cancel');
    Route::get('/xendit/success', 'Payment\XenditController@successPayment')->name('membership.xendit.success');
    Route::get('/yoco/success', 'Payment\YocoController@successPayment')->name('membership.yoco.success');
    Route::get('/toyyibpay/success', 'Payment\ToyyibpayController@successPayment')->name('membership.toyyibpay.success');
    Route::post('/paytabs/success', 'Payment\PaytabsController@successPayment')->name('membership.paytabs.success');
    Route::get('/midtrans/success', 'Payment\MidtransController@successPayment')->name('membership.midtrans.success');
    Route::post('/iyzico/success', 'Payment\IyzicoController@successPayment')->name('membership.iyzico.success');
    Route::post('/arb/success', 'Payment\ArbController@successPayment')->name('membership.arb.success');
    Route::post('/arb/cancel', 'Payment\ArbController@failedPayment')->name('membership.arb.cancel');
    Route::get('/offline/success', 'Front\CheckoutController@offlineSuccess')->name('membership.offline.success')->middleware('setlang');
    Route::get('/trial/success', 'Front\CheckoutController@trialSuccess')->name('membership.trial.success')->middleware('setlang');
});

// Item Checkout Payment Gateways
Route::prefix('item-checkout')->group(function () {
    Route::get('paypal/success', "User\Payment\PaypalController@successPayment")->name('customer.itemcheckout.paypal.success');
    Route::get('paypal/cancel', "User\Payment\PaypalController@cancelPayment")->name('customer.itemcheckout.paypal.cancel');
    Route::get('stripe/cancel', "User\Payment\StripeController@cancelPayment")->name('customer.itemcheckout.stripe.cancel');
    Route::get('paystack/success', 'User\Payment\PaystackController@successPayment')->name('customer.itemcheckout.paystack.success');
    Route::post('mercadopago/success', 'User\Payment\MercadopagoController@successPayment')->name('customer.itemcheckout.mercadopago.success');
    Route::post('razorpay/success', 'User\Payment\RazorpayController@successPayment')->name('customer.itemcheckout.razorpay.success');
    Route::post('razorpay/cancel', 'User\Payment\RazorpayController@cancelPayment')->name('customer.itemcheckout.razorpay.cancel');
    Route::get('instamojo/success', 'User\Payment\InstamojoController@successPayment')->name('customer.itemcheckout.instamojo.success');
    Route::post('instamojo/cancel', 'User\Payment\InstamojoController@cancelPayment')->name('customer.itemcheckout.instamojo.cancel');
    Route::post('flutterwave/success', 'User\Payment\FlutterWaveController@successPayment')->name('customer.itemcheckout.flutterwave.success');
    Route::post('flutterwave/cancel', 'User\Payment\FlutterWaveController@cancelPayment')->name('customer.itemcheckout.flutterwave.cancel');
    Route::get('/mollie/success', 'User\Payment\MollieController@successPayment')->name('customer.itemcheckout.mollie.success');
    Route::post('mollie/cancel', 'User\Payment\MollieController@cancelPayment')->name('customer.itemcheckout.mollie.cancel');
    Route::post('/phonepe/success', 'User\Payment\PhonePeController@successPayment')->name('customer.itemcheckout.phonepe.success');
    Route::post('phonepe/cancel', 'User\Payment\PhonePeController@cancelPayment')->name('customer.itemcheckout.phonepe.cancel');
    Route::get('/perfect_money/success', 'User\Payment\PerfectMoneyController@successPayment')->name('customer.itemcheckout.perfect_money.success');
    Route::get('perfect_money/cancel', 'User\Payment\PerfectMoneyController@cancelPayment')->name('customer.itemcheckout.perfect_money.cancel');
    Route::get('/xendit/success', 'User\Payment\XenditController@successPayment')->name('customer.itemcheckout.xendit.success');
    Route::get('/yoco/success', 'User\Payment\YocoController@successPayment')->name('customer.itemcheckout.yoco.success');
    Route::get('/toyyibpay/success', 'User\Payment\ToyyibpayController@successPayment')->name('customer.itemcheckout.toyyibpay.success');
    Route::post('/paytabs/success', 'User\Payment\PaytabsController@successPayment')->name('customer.itemcheckout.paytabs.success');
    Route::get('/midtrans/success', 'User\Payment\MidtransController@successPayment')->name('customer.itemcheckout.midtrans.success');
    Route::post('/iyzico/success', 'User\Payment\IyzicoController@successPayment')->name('customer.itemcheckout.iyzico.success');
    Route::get('anet/cancel', 'User\Payment\AuthorizenetController@cancelPayment')->name('customer.itemcheckout.anet.cancel');
    Route::get('/offline/success', 'Front\UsercheckoutController@offlineSuccess')->name('customer.itemcheckout.offline.success');
    Route::get('/trial/success', 'Front\CheckoutController@trialSuccess')->name('customer.itemcheckout.trial.success');
    Route::post('paytm/payment-status', "User\Payment\PaytmController@paymentStatus")->name('customer.itemcheckout.paytm.status');
});
