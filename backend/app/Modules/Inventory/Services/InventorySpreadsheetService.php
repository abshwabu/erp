<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Enums\ProductStatus;
use App\Modules\Inventory\Enums\ProductType;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Models\ReorderSetting;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class InventorySpreadsheetService
{
    /**
     * Standard columns for the inventory template matching database schema.
     */
    public const COLUMNS = [
        'sku'             => 'SKU',
        'name'            => 'Product Name',
        'category'        => 'Category',
        'type'            => 'Type (stockable/consumable/service)',
        'cost_price'      => 'Cost Price',
        'selling_price'   => 'Selling Price',
        'initial_stock'   => 'Initial Stock',
        'warehouse_code'  => 'Warehouse Code',
        'barcode'         => 'Barcode',
        'unit_of_measure' => 'Unit of Measure',
        'min_stock_level' => 'Min Stock / Alert Level',
        'description'     => 'Description',
    ];

    /**
     * Realistic sample rows for template download.
     */
    public const SAMPLE_ROWS = [
        [
            'sku'             => 'PRD-OIL-5W30',
            'name'            => 'Total Quartz 9000 5W-30 (4L)',
            'category'        => 'Lubricants',
            'type'            => 'stockable',
            'cost_price'      => '3200.00',
            'selling_price'   => '4100.00',
            'initial_stock'   => '45',
            'warehouse_code'  => 'WH-MAIN',
            'barcode'         => '600109283741',
            'unit_of_measure' => 'Bottle',
            'min_stock_level' => '10',
            'description'     => 'Full synthetic high-performance engine oil',
        ],
        [
            'sku'             => 'PRD-FLT-001',
            'name'            => 'Toyota Genuine Oil Filter (90915-YZZE1)',
            'category'        => 'Spare Parts',
            'type'            => 'stockable',
            'cost_price'      => '450.00',
            'selling_price'   => '750.00',
            'initial_stock'   => '120',
            'warehouse_code'  => 'WH-MAIN',
            'barcode'         => '600109283742',
            'unit_of_measure' => 'Pcs',
            'min_stock_level' => '25',
            'description'     => 'OEM replacement oil filter for Toyota engines',
        ],
        [
            'sku'             => 'PRD-BRK-PAD',
            'name'            => 'Brembo Front Brake Pads Set',
            'category'        => 'Brake System',
            'type'            => 'stockable',
            'cost_price'      => '1800.00',
            'selling_price'   => '2500.00',
            'initial_stock'   => '30',
            'warehouse_code'  => 'WH-MAIN',
            'barcode'         => '600109283743',
            'unit_of_measure' => 'Set',
            'min_stock_level' => '5',
            'description'     => 'Premium ceramic low-dust front brake pads',
        ],
        [
            'sku'             => 'PRD-SRV-CHG',
            'name'            => 'Standard Oil & Filter Service (Labor)',
            'category'        => 'Services',
            'type'            => 'service',
            'cost_price'      => '0.00',
            'selling_price'   => '500.00',
            'initial_stock'   => '0',
            'warehouse_code'  => 'WH-MAIN',
            'barcode'         => '',
            'unit_of_measure' => 'Service',
            'min_stock_level' => '0',
            'description'     => 'Complete labor service for oil change and inspection',
        ],
    ];

    /**
     * Generate a spreadsheet template (XLSX or CSV).
     *
     * @return array{filename: string, content: string, mime: string}
     */
    public function generateTemplate(string $format = 'xlsx'): array
    {
        $format = strtolower(trim($format));

        if ($format === 'csv') {
            return $this->generateCsvTemplate();
        }

        return $this->generateXlsxTemplate();
    }

    /**
     * Generate CSV template with UTF-8 BOM.
     */
    public function generateCsvTemplate(): array
    {
        $headers = array_keys(self::COLUMNS);
        $output  = fopen('php://memory', 'r+');

        // Write UTF-8 BOM for Microsoft Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Write header row
        fputcsv($output, $headers);

        // Write sample rows
        foreach (self::SAMPLE_ROWS as $row) {
            $line = [];
            foreach ($headers as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($output, $line);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return [
            'filename' => 'inventory_import_template.csv',
            'content'  => (string) $content,
            'mime'     => 'text/csv; charset=UTF-8',
        ];
    }

    /**
     * Generate XLSX template using pure PHP ZipArchive and OpenXML specification.
     */
    public function generateXlsxTemplate(): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'inv_tpl_') . '.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            // Fallback to CSV if zip cannot be created
            return $this->generateCsvTemplate();
        }

        $headers = array_keys(self::COLUMNS);
        $headerLabels = array_values(self::COLUMNS);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="Inventory Template" sheetId="1" r:id="rId1"/>'
            . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml (Professional header styling: Blue background, bold white text)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><name val="Calibri"/><sz val="11"/></font>'
            . '<font><b/><name val="Calibri"/><sz val="11"/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1D4ED8"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center"/>'
            . '</xf>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. Build Sheet Data with Column Widths
        $colWidths = [18, 38, 20, 22, 14, 14, 14, 18, 18, 16, 20, 45];
        $colsXml = '<cols>';
        foreach ($colWidths as $idx => $w) {
            $colNum = $idx + 1;
            $colsXml .= sprintf('<col min="%d" max="%d" width="%d" customWidth="1"/>', $colNum, $colNum, $w);
        }
        $colsXml .= '</cols>';

        $sheetDataXml = '<sheetData>';

        // Row 1: Headers (with style 1 = bold white on blue)
        $sheetDataXml .= '<row r="1" ht="28" customHeight="1">';
        foreach ($headers as $colIdx => $colKey) {
            $cellRef = self::numToCol($colIdx) . '1';
            $val = htmlspecialchars($colKey, ENT_XML1, 'UTF-8');
            $sheetDataXml .= sprintf('<c r="%s" t="inlineStr" s="1"><is><t>%s</t></is></c>', $cellRef, $val);
        }
        $sheetDataXml .= '</row>';

        // Rows 2+: Sample rows
        $rowNum = 2;
        foreach (self::SAMPLE_ROWS as $sample) {
            $sheetDataXml .= sprintf('<row r="%d" ht="20" customHeight="1">', $rowNum);
            foreach ($headers as $colIdx => $colKey) {
                $cellRef = self::numToCol($colIdx) . $rowNum;
                $rawVal = (string) ($sample[$colKey] ?? '');
                $val = htmlspecialchars($rawVal, ENT_XML1, 'UTF-8');
                $sheetDataXml .= sprintf('<c r="%s" t="inlineStr"><is><t>%s</t></is></c>', $cellRef, $val);
            }
            $sheetDataXml .= '</row>';
            $rowNum++;
        }

        $sheetDataXml .= '</sheetData>';

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $colsXml
            . $sheetDataXml
            . '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return [
            'filename' => 'inventory_import_template.xlsx',
            'content'  => (string) $content,
            'mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    /**
     * Parse an uploaded file or path into standardized rows.
     *
     * @return array<array<string, mixed>>
     */
    public function parseFile(UploadedFile|string $file): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($filePath);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === 'xlsx' || $extension === 'xls') {
            return $this->parseXlsx($filePath);
        }

        return $this->parseCsv($filePath);
    }

    /**
     * Parse XLSX file using ZipArchive and XML parsing.
     */
    public function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Unable to read Excel (.xlsx) file. Please ensure it is a valid Excel workbook.');
        }

        // 1. Read shared strings if present
        $sharedStrings = [];
        $sstXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sstXml) {
            $sst = @simplexml_load_string($sstXml);
            if ($sst && isset($sst->si)) {
                foreach ($sst->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        // Rich text run
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) ($r->t ?? '');
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read sheet1.xml (or first worksheet)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            // Find first sheet from workbook rels
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (preg_match('#xl/worksheets/sheet\d+\.xml#i', $entry)) {
                    $sheetXml = $zip->getFromName($entry);
                    break;
                }
            }
        }

        $zip->close();

        if (!$sheetXml) {
            throw new \RuntimeException('No worksheet found inside the Excel file.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return [];
        }

        $rawGrid = [];
        foreach ($xml->sheetData->row as $row) {
            $rNum = (int) ($row['r'] ?? (count($rawGrid) + 1));
            $rowCells = [];

            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                preg_match('/([A-Z]+)(\d+)/', $ref, $matches);
                $colLetters = $matches[1] ?? 'A';
                $colIdx = self::colToNum($colLetters);

                $type = (string) ($c['t'] ?? '');
                $val = '';

                if ($type === 's') {
                    // Shared string index
                    $idx = (int) $c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) ($c->is->t ?? '');
                } else {
                    $val = (string) ($c->v ?? '');
                }

                $rowCells[$colIdx] = trim($val);
            }

            if (!empty($rowCells)) {
                // Fill any skipped gaps
                $maxCol = max(array_keys($rowCells));
                $fullRow = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $fullRow[$i] = $rowCells[$i] ?? '';
                }
                $rawGrid[] = $fullRow;
            }
        }

        return $this->gridToAssociativeRows($rawGrid);
    }

    /**
     * Parse CSV, TSV or text file with auto-delimiter detection.
     */
    public function parseCsv(string $filePath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Remove UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\n|\r/', trim($content));
        if (empty($lines)) {
            return [];
        }

        // Autodetect delimiter from header line
        $firstLine = $lines[0];
        $delimiters = [',', ';', "\t", '|'];
        $bestDelim = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelim = $delim;
            }
        }

        $rawGrid = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line, $bestDelim);
            $rawGrid[] = array_map('trim', $row);
        }

        return $this->gridToAssociativeRows($rawGrid);
    }

    /**
     * Convert 2D cell grid into normalized associative rows.
     */
    protected function gridToAssociativeRows(array $grid): array
    {
        if (count($grid) < 2) {
            return [];
        }

        $headerRow = array_shift($grid);
        $headerMap = [];

        foreach ($headerRow as $idx => $headerText) {
            $norm = $this->normalizeHeader((string) $headerText);
            if ($norm !== null) {
                $headerMap[$idx] = $norm;
            }
        }

        if (empty($headerMap)) {
            return [];
        }

        $rows = [];
        foreach ($grid as $rowIdx => $cellValues) {
            $row = [];
            $hasData = false;

            foreach ($headerMap as $colIdx => $key) {
                $val = $cellValues[$colIdx] ?? '';
                $row[$key] = $val;
                if (!empty($val)) {
                    $hasData = true;
                }
            }

            if ($hasData) {
                $row['_line_number'] = $rowIdx + 2; // Accounting for 1-based header row
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Map arbitrary user header labels to database keys.
     */
    protected function normalizeHeader(string $raw): ?string
    {
        $clean = strtolower(trim(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '-', '/'], '_', $raw)) ?? ''));

        return match ($clean) {
            'sku', 'product_sku', 'item_code', 'code', 'product_code', 'part_number' => 'sku',
            'name', 'product_name', 'item_name', 'title' => 'name',
            'category', 'product_category', 'category_name' => 'category',
            'type', 'product_type', 'item_type' => 'type',
            'cost_price', 'cost', 'unit_cost', 'purchase_price', 'buy_price' => 'cost_price',
            'selling_price', 'price', 'sale_price', 'unit_price', 'retail_price' => 'selling_price',
            'initial_stock', 'stock', 'quantity', 'qty', 'stock_on_hand', 'quantity_on_hand', 'opening_stock' => 'initial_stock',
            'warehouse_code', 'warehouse', 'location', 'location_code', 'store' => 'warehouse_code',
            'barcode', 'bar_code', 'upc', 'ean', 'isbn' => 'barcode',
            'unit_of_measure', 'unit', 'uom', 'measurement' => 'unit_of_measure',
            'min_stock_level', 'min_stock', 'reorder_level', 'alert_quantity', 'reorder_quantity' => 'min_stock_level',
            'description', 'desc', 'details', 'notes' => 'description',
            'status' => 'status',
            'has_variants' => 'has_variants',
            default => null,
        };
    }

    /**
     * Import parsed inventory rows into the database.
     *
     * @param array<array<string, mixed>> $rows
     * @param array{update_existing?: bool, default_location_id?: string|null} $options
     * @return array{total_rows: int, created_count: int, updated_count: int, skipped_count: int, failed_count: int, errors: array<array{row: int, sku: string, error: string}>}
     */
    public function importRows(array $rows, array $options = []): array
    {
        $updateExisting = $options['update_existing'] ?? true;
        $defaultLocationId = $options['default_location_id'] ?? null;

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors       = [];

        // Cache categories and locations for fast lookups
        $categoriesCache = [];
        $locationsCache  = [];

        // Resolve default location
        $defaultLocation = null;
        if (!empty($defaultLocationId)) {
            $defaultLocation = StockLocation::find($defaultLocationId);
        }

        if (!$defaultLocation) {
            $defaultLocation = $this->getOrCreateDefaultLocation();
        }

        $userId = auth()->id() ?? User::first()?->id;

        foreach ($rows as $index => $row) {
            $lineNum = $row['_line_number'] ?? ($index + 2);
            $sku     = trim((string) ($row['sku'] ?? ''));
            $name    = trim((string) ($row['name'] ?? ''));

            if (empty($name)) {
                $errors[] = [
                    'row'   => $lineNum,
                    'sku'   => $sku,
                    'error' => 'Product Name is required.',
                ];
                continue;
            }

            if (empty($sku)) {
                $sku = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'PRD', 0, 6))
                    . '-' . strtoupper(substr(uniqid(), -5));
            }

            try {
                DB::transaction(function () use (
                    $row,
                    $lineNum,
                    $sku,
                    $name,
                    $updateExisting,
                    $defaultLocation,
                    $userId,
                    &$createdCount,
                    &$updatedCount,
                    &$skippedCount,
                    &$categoriesCache,
                    &$locationsCache
                ) {
                    // 1. Resolve Category
                    $categoryId = null;
                    $catName = trim((string) ($row['category'] ?? ''));
                    if (!empty($catName)) {
                        if (!isset($categoriesCache[$catName])) {
                            $slug = Str::slug($catName);
                            $cat = ProductCategory::firstOrCreate(
                                ['slug' => $slug],
                                ['name' => $catName, 'is_active' => true]
                            );
                            $categoriesCache[$catName] = $cat->id;
                        }
                        $categoryId = $categoriesCache[$catName];
                    }

                    // 2. Parse prices and enums
                    $typeStr = strtolower(trim((string) ($row['type'] ?? 'stockable')));
                    $type = ProductType::tryFrom($typeStr) ?? ProductType::Stockable;

                    $statusStr = strtolower(trim((string) ($row['status'] ?? 'active')));
                    $status = ProductStatus::tryFrom($statusStr) ?? ProductStatus::Active;

                    $costFloat = (float) filter_var($row['cost_price'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $sellFloat = (float) filter_var($row['selling_price'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $costCents = (int) round($costFloat * 100);
                    $sellCents = (int) round($sellFloat * 100);

                    // 3. Find or Create Product
                    $existingProduct = Product::where('sku', $sku)->first();

                    if ($existingProduct) {
                        if (!$updateExisting) {
                            $skippedCount++;
                            return;
                        }

                        $existingProduct->update(array_filter([
                            'name'          => $name,
                            'description'   => $row['description'] ?? $existingProduct->description,
                            'type'          => $type,
                            'status'        => $status,
                            'category_id'   => $categoryId ?? $existingProduct->category_id,
                            'cost_price'    => $costCents,
                            'selling_price' => $sellCents,
                        ], fn ($v) => $v !== null));

                        $product = $existingProduct;
                        $updatedCount++;
                    } else {
                        $product = Product::create([
                            'sku'           => $sku,
                            'name'          => $name,
                            'description'   => $row['description'] ?? null,
                            'type'          => $type,
                            'status'        => $status,
                            'category_id'   => $categoryId,
                            'cost_price'    => $costCents,
                            'selling_price' => $sellCents,
                            'has_variants'  => false,
                        ]);
                        $createdCount++;
                    }

                    // 4. Primary Barcode
                    $barcodeVal = trim((string) ($row['barcode'] ?? ''));
                    if (!empty($barcodeVal)) {
                        $len = strlen($barcodeVal);
                        $barcodeType = match (true) {
                            $len === 12 && ctype_digit($barcodeVal) => \App\Modules\Inventory\Enums\BarcodeType::UPCA,
                            $len === 13 && ctype_digit($barcodeVal) => \App\Modules\Inventory\Enums\BarcodeType::EAN13,
                            default => \App\Modules\Inventory\Enums\BarcodeType::CODE128,
                        };

                        $product->barcodes()->firstOrCreate(
                            ['barcode' => $barcodeVal],
                            ['type' => $barcodeType, 'is_primary' => true]
                        );
                    }

                    // 5. Initial Stock & Location
                    $initialStock = (int) filter_var($row['initial_stock'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
                    $locationCode = trim((string) ($row['warehouse_code'] ?? ''));
                    $targetLocation = $defaultLocation;

                    if (!empty($locationCode)) {
                        if (!isset($locationsCache[$locationCode])) {
                            $loc = StockLocation::where('code', $locationCode)->first();
                            if (!$loc) {
                                // Try finding warehouse by code and getting its storage location
                                $wh = Warehouse::where('code', $locationCode)->first();
                                if ($wh) {
                                    $loc = StockLocation::where('warehouse_id', $wh->id)->first();
                                }
                            }
                            $locationsCache[$locationCode] = $loc ?: $defaultLocation;
                        }
                        $targetLocation = $locationsCache[$locationCode];
                    }

                    if ($targetLocation && $type === ProductType::Stockable) {
                        $level = StockLevel::firstOrCreate([
                            'product_id'  => $product->id,
                            'location_id' => $targetLocation->id,
                            'variant_id'  => null,
                        ], [
                            'quantity_on_hand'   => 0,
                            'quantity_committed' => 0,
                            'quantity_on_order'  => 0,
                        ]);

                        if ($initialStock > 0) {
                            if (!$existingProduct) {
                                // New product: record opening balance
                                StockMovement::create([
                                    'product_id'     => $product->id,
                                    'to_location_id' => $targetLocation->id,
                                    'quantity'       => $initialStock,
                                    'type'           => 'opening',
                                    'reference_type' => 'SpreadsheetImport',
                                    'reference_id'   => (string) $product->id,
                                    'unit_cost'      => $costCents,
                                    'currency_code'  => $product->currency_code ?: 'USD',
                                    'user_id'        => $userId,
                                ]);
                            } elseif ($updateExisting && $level->quantity_on_hand !== $initialStock) {
                                // Existing product adjustment if stock is different
                                $diff = $initialStock - $level->quantity_on_hand;
                                if ($diff > 0) {
                                    StockMovement::create([
                                        'product_id'     => $product->id,
                                        'to_location_id' => $targetLocation->id,
                                        'quantity'       => $diff,
                                        'type'           => 'adjustment',
                                        'reference_type' => 'SpreadsheetImportAdjustment',
                                        'reference_id'   => (string) $product->id,
                                        'unit_cost'      => $costCents,
                                        'currency_code'  => $product->currency_code ?: 'USD',
                                        'user_id'        => $userId,
                                    ]);
                                } elseif ($diff < 0) {
                                    StockMovement::create([
                                        'product_id'       => $product->id,
                                        'from_location_id' => $targetLocation->id,
                                        'quantity'         => abs($diff),
                                        'type'             => 'adjustment',
                                        'reference_type'   => 'SpreadsheetImportAdjustment',
                                        'reference_id'     => (string) $product->id,
                                        'unit_cost'        => $costCents,
                                        'currency_code'    => $product->currency_code ?: 'USD',
                                        'user_id'          => $userId,
                                    ]);
                                }
                            }
                        }

                        // 6. Reorder Setting (Min Stock Level)
                        $minStock = (int) filter_var($row['min_stock_level'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
                        if ($minStock > 0) {
                            ReorderSetting::updateOrCreate([
                                'product_id'  => $product->id,
                                'location_id' => $targetLocation->id,
                            ], [
                                'min_quantity'     => $minStock,
                                'max_quantity'     => max($minStock * 5, 100),
                                'reorder_quantity' => max($minStock * 2, 10),
                                'is_auto_reorder'  => false,
                            ]);
                        }
                    }
                });
            } catch (\Throwable $e) {
                Log::warning("InventorySpreadsheetService: error importing row {$lineNum}: " . $e->getMessage());
                $errors[] = [
                    'row'   => $lineNum,
                    'sku'   => $sku,
                    'name'  => $name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_rows'     => count($rows),
            'created_count'  => $createdCount,
            'updated_count'  => $updatedCount,
            'skipped_count'  => $skippedCount,
            'failed_count'   => count($errors),
            'errors'         => $errors,
        ];
    }

    /**
     * Get or create default warehouse and location.
     */
    protected function getOrCreateDefaultLocation(): StockLocation
    {
        $location = StockLocation::where('is_active', true)->first();
        if ($location) {
            return $location;
        }

        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'WH-MAIN'],
            ['name' => 'Main Warehouse', 'type' => 'own', 'is_active' => true]
        );

        return StockLocation::firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'warehouse_id' => $warehouse->id,
                'name'         => 'Main Warehouse',
                'type'         => 'storage',
                'is_active'    => true,
            ]
        );
    }

    /**
     * Convert 0-indexed column integer (0 = A, 27 = AB) to Excel column letters.
     */
    public static function numToCol(int $num): string
    {
        $col = '';
        while ($num >= 0) {
            $col = chr(($num % 26) + 65) . $col;
            $num = intdiv($num, 26) - 1;
        }
        return $col;
    }

    /**
     * Convert Excel column letters (A = 0, AB = 27) to 0-indexed column integer.
     */
    public static function colToNum(string $col): int
    {
        $col = strtoupper($col);
        $num = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $num = $num * 26 + (ord($col[$i]) - 64);
        }
        return $num - 1;
    }
}
