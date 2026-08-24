<?php

namespace App\Imports\Support;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SheetPicker implements WithMultipleSheets
{
    /** @var object */
    private $inner;

    /** @var int */
    private $index;

    public function __construct(object $inner, int $index)
    {
        $this->inner = $inner;
        $this->index = $index;
    }

    public function sheets(): array
    {
        return [
            $this->index => $this->inner,
        ];
    }
}
