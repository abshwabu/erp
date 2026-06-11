<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Events\LowStockDetected;
use App\Modules\Inventory\Models\ReorderSetting;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckReorderLevelsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(StockService $stockService): void
    {
        $settings = ReorderSetting::with(['product', 'location'])->get();

        foreach ($settings as $setting) {
            if (!$setting->product) {
                continue;
            }

            $available = $stockService->getAvailableQty($setting->product, $setting->location);

            if ($available <= $setting->min_quantity) {
                event(new LowStockDetected(
                    $setting->product,
                    $setting->location,
                    $available,
                    (int) $setting->min_quantity
                ));
            }
        }
    }
}
