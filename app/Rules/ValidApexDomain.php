<?php

namespace App\Rules;

use App\Support\ApexDomainValidator;
use Illuminate\Contracts\Validation\Rule;

class ValidApexDomain implements Rule
{
    private ?string $error = null;

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        if (! is_string($value)) {
            $this->error = 'The domain must be a valid domain name.';

            return false;
        }

        $normalized = ApexDomainValidator::normalize($value);
        $this->error = ApexDomainValidator::validate($normalized);

        return $this->error === null;
    }

    public function message(): string
    {
        return $this->error ?? 'The domain must be a valid domain name.';
    }
}
