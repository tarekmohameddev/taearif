<?php

return [
    'currency' => 'SAR',

    // Base yearly price; periods can apply multipliers/discounts
    'base_yearly_price' => 50.00,

    // Supported renewal periods
    'periods' => [
        '1_year' => [
            'label' => 'سنة واحدة',
            'years' => 1,
            'multiplier' => 1.0,
        ],
        '2_years' => [
            'label' => 'سنتان',
            'years' => 2,
            'multiplier' => 1.8, // discount vs 2x base
        ],
    ],
];


