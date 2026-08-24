<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PreservesNumericStringsBinder extends DefaultValueBinder
{
    /**
     * Force digit-like strings (phones, deed IDs, country codes, etc.) to remain text
     * so leading zeros and long-ID precision are not lost to numeric coercion.
     *
     * @param  mixed  $value
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && $value !== '' && preg_match('/^[+\-]?\d+$/', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
