<?php
namespace App\Enums;

enum BillingType: string
{
    case Free         = 'free';
    case Paid         = 'paid';          // one-off
    case PaidTrial    = 'paid_trial';    // “paid with 15-day trial”
}
