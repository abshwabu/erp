<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Services\InventorySpreadsheetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportProductsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        private readonly string $filePath,
        private readonly array $options = []
    ) {}

    public function handle(): void
    {
        $content = Storage::get($this->filePath);

        if ($content === null) {
            Log::error("ImportProductsJob: file not found at {$this->filePath}");
            return;
        }

        try {
            $service = app(InventorySpreadsheetService::class);
            $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION)) ?: 'csv';
            $tempFile = tempnam(sys_get_temp_dir(), 'imp_job_') . '.' . $extension;
            file_put_contents($tempFile, $content);

            $rows = $service->parseFile($tempFile);
            @unlink($tempFile);

            $service->importRows($rows, array_merge(['update_existing' => true], $this->options));
        } catch (\Throwable $e) {
            Log::warning("ImportProductsJob: failed to process import file: " . $e->getMessage());
        } finally {
            // Clean up uploaded file after processing
            Storage::delete($this->filePath);
        }
    }
}
