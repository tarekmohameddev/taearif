<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidProjectSlug implements Rule
{
    /**
     * Reserved slugs that cannot be used
     */
    protected $reservedSlugs = [
        'create',
        'edit',
        'new',
        'delete',
        'update',
        'store',
        'destroy',
        'index',
        'show',
        'api',
        'admin',
    ];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Check if slug is reserved
        if (in_array(strtolower($value), $this->reservedSlugs)) {
            return false;
        }

        // Check if slug contains only valid characters (letters, numbers, hyphens)
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            return false;
        }

        // Check minimum length
        if (strlen($value) < 3) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is invalid. It must be at least 3 characters, contain only lowercase letters, numbers, and hyphens, and cannot be a reserved word.';
    }
}

