<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class WholeNumber implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * Numeric validation is intentionally left to Laravel's numeric rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!is_numeric($value)) {
            return true;
        }

        $numericValue = (float) $value;

        return is_finite($numericValue) && floor($numericValue) == $numericValue;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a whole number. Values ending in .00 are allowed.';
    }
}
