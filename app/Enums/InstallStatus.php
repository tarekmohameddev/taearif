<?php
namespace App\Enums;

enum InstallStatus: string
{
    case Trialing        = 'trialing';
    case PendingPayment  = 'pending_payment';
    case Installed       = 'installed';
    case Uninstalled     = 'uninstalled';
}
