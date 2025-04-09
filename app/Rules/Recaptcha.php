<?php

namespace App\Rules;

use Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Validation\Rule;

class Recaptcha implements Rule
{
    public function passes($attribute, $value)
{
    $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        'secret' => config('services.recaptcha.secret'),
        'response' => $value,
    ]);

    $data = $response->json();
    \Log::info('Recaptcha response:', $data);

    // Optional: reject if score is too low
    if (!($data['success'] ?? false) || ($data['score'] ?? 0) < 0.5) {
        return false;
    }

    return true;
}

    public function message()
    {
        return 'reCAPTCHA verification failed.';
    }
}
