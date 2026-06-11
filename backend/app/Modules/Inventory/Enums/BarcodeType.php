<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum BarcodeType: string
{
    case EAN13 = 'EAN13';
    case UPCA = 'UPCA';
    case CODE128 = 'CODE128';
    case QR = 'QR';
}
