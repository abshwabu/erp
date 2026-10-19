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
     * Complete catalogue of exportable fields and their human-readable labels.
     */
    public const EXPORTABLE_COLUMNS = [
        'name'               => 'Product Name',
        'sku'                => 'SKU',
        'category'           => 'Category',
        'selling_price'      => 'Selling Price',
        'cost_price'         => 'Cost Price',
        'quantity_on_hand'   => 'Total Stock',
        'available_quantity' => 'Available Stock',
        'type'               => 'Type',
        'status'             => 'Status',
        'barcode'            => 'Barcode',
        'description'        => 'Description',
        'created_at'         => 'Created At',
    ];

    /**
     * Default columns selected if user does not customize.
     */
    public const DEFAULT_EXPORT_COLUMNS = [
        'name',
        'sku',
        'category',
        'selling_price',
        'quantity_on_hand',
        'status',
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
        fputcsv($output, $headers, ',', '"', '\\');

        // Write sample rows
        foreach (self::SAMPLE_ROWS as $row) {
            $line = [];
            foreach ($headers as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($output, $line, ',', '"', '\\');
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
     * Export products based on user-selected columns, filters, and output format.
     *
     * @param string[] $columns User-specified column keys to include (e.g. ['name', 'selling_price', 'description'])
     * @param array<string, mixed> $filters
     * @param string[]|int[]|null $productIds
     * @param string $format 'xlsx' | 'csv' | 'pdf'
     * @return array{filename: string, content: string, mime: string}
     */
    public function exportProducts(
        array $columns = [],
        array $filters = [],
        ?array $productIds = null,
        string $format = 'xlsx'
    ): array {
        // 1. Resolve selected columns
        $selectedColumns = [];
        if (!empty($columns)) {
            foreach ($columns as $col) {
                $colKey = trim((string) $col);
                if (isset(self::EXPORTABLE_COLUMNS[$colKey])) {
                    $selectedColumns[$colKey] = self::EXPORTABLE_COLUMNS[$colKey];
                }
            }
        }

        if (empty($selectedColumns)) {
            foreach (self::DEFAULT_EXPORT_COLUMNS as $colKey) {
                if (isset(self::EXPORTABLE_COLUMNS[$colKey])) {
                    $selectedColumns[$colKey] = self::EXPORTABLE_COLUMNS[$colKey];
                }
            }
        }

        // 2. Query products with eager relationships
        $query = Product::query()
            ->with(['category', 'barcodes', 'stockLevels']);

        if (!empty($productIds)) {
            $query->whereIn('id', $productIds);
        } else {
            if (!empty($filters['search'])) {
                $search = trim((string) $filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%")
                        ->orWhereHas('barcodes', function ($bq) use ($search) {
                            $bq->where('barcode', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('category', function ($cq) use ($search) {
                            $cq->where('name', 'ilike', "%{$search}%");
                        });
                });
            }

            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
        }

        $query->orderBy('name');
        $products = $query->get();

        // 3. Transform to rows
        $columnKeys = array_keys($selectedColumns);
        $rows = [];
        foreach ($products as $product) {
            $rows[] = $this->formatProductRow($product, $columnKeys);
        }

        // 4. Generate in requested format
        $format = strtolower(trim($format));
        return match ($format) {
            'csv' => $this->generateCsvExport($selectedColumns, $rows, 'Products Catalog', 'products_export'),
            'pdf' => $this->generatePdfExport($selectedColumns, $rows, 'Products Catalog Export', 'products_export'),
            default => $this->generateXlsxExport($selectedColumns, $rows, 'Products Catalog', 'products_export'),
        };
    }

    /**
     * Format a single Product model into an associative array for export.
     *
     * @param string[] $columnKeys
     * @return array<string, string>
     */
    public function formatProductRow(Product $product, array $columnKeys): array
    {
        $row = [];
        foreach ($columnKeys as $col) {
            $row[$col] = match ($col) {
                'name' => (string) ($product->name ?? ''),
                'sku' => (string) ($product->sku ?? ''),
                'category' => (string) ($product->category?->name ?? 'Uncategorized'),
                'type' => (string) (is_object($product->type) ? $product->type->value : ($product->type ?? 'stockable')),
                'status' => (string) (is_object($product->status) ? $product->status->value : ($product->status ?? 'active')),
                'selling_price' => number_format(((int) ($product->selling_price ?? 0)) / 100, 2, '.', ''),
                'cost_price' => number_format(((int) ($product->cost_price ?? 0)) / 100, 2, '.', ''),
                'quantity_on_hand' => (string) ($product->relationLoaded('stockLevels')
                    ? ($product->has_variants && $product->stockLevels->whereNotNull('variant_id')->isNotEmpty()
                        ? $product->stockLevels->whereNotNull('variant_id')->sum('quantity_on_hand')
                        : $product->stockLevels->sum('quantity_on_hand'))
                    : 0),
                'available_quantity' => (string) ($product->relationLoaded('stockLevels')
                    ? ($product->has_variants && $product->stockLevels->whereNotNull('variant_id')->isNotEmpty()
                        ? ($product->stockLevels->whereNotNull('variant_id')->sum('quantity_on_hand') - $product->stockLevels->whereNotNull('variant_id')->sum('quantity_committed'))
                        : ($product->stockLevels->sum('quantity_on_hand') - $product->stockLevels->sum('quantity_committed')))
                    : 0),
                'barcode' => (string) ($product->barcodes->firstWhere('is_primary', true)?->barcode
                    ?? $product->barcodes->first()?->barcode
                    ?? ''),
                'description' => (string) ($product->description ?? ''),
                'created_at' => $product->created_at ? $product->created_at->format('Y-m-d H:i') : '',
                default => '',
            };
        }

        return $row;
    }

    /**
     * Generate CSV export with UTF-8 BOM.
     *
     * @param array<string, string> $headers Key => Label
     * @param array<int, array<string, mixed>> $rows
     */
    public function generateCsvExport(
        array $headers,
        array $rows,
        string $title = 'Products Catalog',
        string $filenamePrefix = 'products_export'
    ): array {
        $output = fopen('php://memory', 'r+');

        // Write UTF-8 BOM
        fwrite($output, "\xEF\xBB\xBF");

        // Write header row
        fputcsv($output, array_values($headers), ',', '"', '\\');

        // Write data rows
        $keys = array_keys($headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($keys as $k) {
                $line[] = $row[$k] ?? '';
            }
            fputcsv($output, $line, ',', '"', '\\');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $timestamp = date('Ymd_His');
        return [
            'filename' => "{$filenamePrefix}_{$timestamp}.csv",
            'content'  => (string) $content,
            'mime'     => 'text/csv; charset=UTF-8',
        ];
    }

    /**
     * Generate OpenXML XLSX export using ZipArchive without external libraries.
     *
     * @param array<string, string> $headers Key => Label
     * @param array<int, array<string, mixed>> $rows
     */
    public function generateXlsxExport(
        array $headers,
        array $rows,
        string $title = 'Products Catalog',
        string $filenamePrefix = 'products_export'
    ): array {
        $tempFile = tempnam(sys_get_temp_dir(), 'exp_') . '.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->generateCsvExport($headers, $rows, $title, $filenamePrefix);
        }

        $keys = array_keys($headers);
        $labels = array_values($headers);

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
            . '<sheet name="Products" sheetId="1" r:id="rId1"/>'
            . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml (Professional Navy & Clean Zebra styling)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><name val="Calibri"/><sz val="10"/><color rgb="FF0F172A"/></font>'
            . '<font><b/><name val="Calibri"/><sz val="10"/><color rgb="FFFFFFFF"/></font>'
            . '<font><name val="Calibri"/><sz val="9"/><color rgb="FF64748B"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            // 0: Normal text
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"><alignment vertical="center"/></xf>'
            // 1: Header
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 2: Zebra row
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            // 3: Numeric right-aligned
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 4: Numeric zebra right-aligned
            . '<xf numFmtId="2" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. Dynamic Column Widths
        $colWidths = [];
        foreach ($keys as $idx => $k) {
            $label = $headers[$k] ?? $k;
            $maxLen = mb_strlen($label);
            foreach ($rows as $r) {
                $valLen = mb_strlen((string) ($r[$k] ?? ''));
                if ($valLen > $maxLen) {
                    $maxLen = $valLen;
                }
            }
            $width = min(55, max(14, $maxLen + 3));
            $colWidths[$idx] = $width;
        }

        $colsXml = '<cols>';
        foreach ($colWidths as $idx => $w) {
            $colNum = $idx + 1;
            $colsXml .= sprintf('<col min="%d" max="%d" width="%d" customWidth="1"/>', $colNum, $colNum, $w);
        }
        $colsXml .= '</cols>';

        $sheetDataXml = '<sheetData>';

        // Row 1: Headers
        $sheetDataXml .= '<row r="1" ht="26" customHeight="1">';
        foreach ($labels as $colIdx => $colLabel) {
            $cellRef = self::numToCol($colIdx) . '1';
            $val = htmlspecialchars($colLabel, ENT_XML1, 'UTF-8');
            $sheetDataXml .= sprintf('<c r="%s" t="inlineStr" s="1"><is><t>%s</t></is></c>', $cellRef, $val);
        }
        $sheetDataXml .= '</row>';

        // Rows 2+: Data
        $numericKeys = ['selling_price', 'cost_price', 'quantity_on_hand', 'available_quantity'];
        $rowNum = 2;
        foreach ($rows as $dataRow) {
            $isZebra = ($rowNum % 2 === 1);
            $sheetDataXml .= sprintf('<row r="%d" ht="20" customHeight="1">', $rowNum);

            foreach ($keys as $colIdx => $k) {
                $cellRef = self::numToCol($colIdx) . $rowNum;
                $rawVal = (string) ($dataRow[$k] ?? '');
                $isNum = in_array($k, $numericKeys, true) && is_numeric($rawVal);

                if ($isNum) {
                    $styleId = $isZebra ? 4 : 3;
                    $sheetDataXml .= sprintf('<c r="%s" t="n" s="%d"><v>%s</v></c>', $cellRef, $styleId, $rawVal);
                } else {
                    $styleId = $isZebra ? 2 : 0;
                    $val = htmlspecialchars($rawVal, ENT_XML1, 'UTF-8');
                    $sheetDataXml .= sprintf('<c r="%s" t="inlineStr" s="%d"><is><t>%s</t></is></c>', $cellRef, $styleId, $val);
                }
            }

            $sheetDataXml .= '</row>';
            $rowNum++;
        }

        $sheetDataXml .= '</sheetData>';

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . $colsXml
            . $sheetDataXml
            . '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        $timestamp = date('Ymd_His');
        return [
            'filename' => "{$filenamePrefix}_{$timestamp}.xlsx",
            'content'  => (string) $content,
            'mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    /**
     * Generate standalone PDF export without external dependencies.
     *
     * @param array<string, string> $headers Key => Label
     * @param array<int, array<string, mixed>> $rows
     */
    public function generatePdfExport(
        array $headers,
        array $rows,
        string $title = 'Products Catalog Export',
        string $filenamePrefix = 'products_export'
    ): array {
        $pdfGen = new PurePdfReport(null, count($headers));
        $pdfContent = $pdfGen->build($title, $headers, $rows);

        $timestamp = date('Ymd_His');
        return [
            'filename' => "{$filenamePrefix}_{$timestamp}.pdf",
            'content'  => $pdfContent,
            'mime'     => 'application/pdf',
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
