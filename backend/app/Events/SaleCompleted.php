<?php

declare(strict_types=1);

namespace App\Events;

use App\Modules\POS\Models\POSTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public POSTransaction $transaction)
    {
    }
}
