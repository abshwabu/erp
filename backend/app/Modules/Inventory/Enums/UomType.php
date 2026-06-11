<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum UomType: string
{
    case Mass = 'mass';
    case Volume = 'volume';
    case Count = 'count';
    case Length = 'length';
}
