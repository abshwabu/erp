<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum ProductType: string
{
    case Stockable = 'stockable';
    case Consumable = 'consumable';
    case Service = 'service';
}
