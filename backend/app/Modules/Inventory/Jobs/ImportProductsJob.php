<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Enums\ProductStatus;
use App\Modules\Inventory\Enums\ProductType;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportProductsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        private readonly string $filePath
    ) {}

    public function handle(): void
    {
        $content = Storage::get($this->filePath);

        if ($content === null) {
            Log::error("ImportProductsJob: file not found at {$this->filePath}");
            return;
        }

        $lines  = explode("\n", trim($content));
        $header = str_getcsv(array_shift($lines));

        foreach ($lines as $index => $line) {
            if (empty(trim($line))) {
                continue;
            }

            $csvRow = str_getcsv($line);

            if (count($header) !== count($csvRow)) {
                Log::warning("ImportProductsJob: skipping malformed row at line " . ($index + 2));
                continue;
            }

            $row = array_combine($header, $csvRow);

            try {
                $this->processRow($row);
            } catch (\Throwable $e) {
                Log::warning("ImportProductsJob: failed to import row at line " . ($index + 2) . ": " . $e->getMessage());
            }
        }

        // Clean up uploaded file after processing
        Storage::delete($this->filePath);
    }

    private function processRow(array $row): void
    {
        // Resolve or create category by name
        $categoryId = null;
        if (!empty($row['category'])) {
            $slug = Str::slug($row['category']);
            $category = ProductCategory::firstOrCreate(
                ['slug' => $slug],
                ['name' => $row['category'], 'is_active' => true]
            );
            $categoryId = $category->id;
        }

        // Resolve enums with fallback to defaults
        $type   = ProductType::tryFrom($row['type'] ?? '')   ?? ProductType::Stockable;
        $status = ProductStatus::tryFrom($row['status'] ?? '') ?? ProductStatus::Active;

        Product::updateOrCreate(
            ['sku' => trim($row['sku'])],
            [
                'name'          => trim($row['name']),
                'description'   => $row['description'] ?? null,
                'type'          => $type,
                'status'        => $status,
                'category_id'   => $categoryId,
                'cost_price'    => (int) round((float) ($row['cost_price'] ?? 0) * 100),
                'selling_price' => (int) round((float) ($row['selling_price'] ?? 0) * 100),
                'has_variants'  => filter_var($row['has_variants'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]
        );
    }
}
