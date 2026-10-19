<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Modules\Inventory\Services\InventorySpreadsheetService;
use App\Modules\Inventory\Services\PurePdfReport;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class InventoryExportTest extends TestCase
{
    private InventorySpreadsheetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventorySpreadsheetService();
    }

    public function test_csv_export_generates_valid_csv_with_selected_columns(): void
    {
        $headers = [
            'name'          => 'Product Name',
            'selling_price' => 'Selling Price',
        ];

        $rows = [
            ['name' => 'Brembo Brake Pads', 'selling_price' => '25.00'],
            ['name' => 'Total Quartz 5W-30', 'selling_price' => '41.50'],
        ];

        $result = $this->service->generateCsvExport($headers, $rows, 'Custom Export', 'test_export');

        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('mime', $result);
        $this->assertSame('text/csv; charset=UTF-8', $result['mime']);
        $this->assertStringStartsWith('test_export_', $result['filename']);
        $this->assertStringEndsWith('.csv', $result['filename']);

        // Check BOM and content
        $content = $result['content'];
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('"Product Name","Selling Price"', $content);
        $this->assertStringContainsString('"Brembo Brake Pads",25.00', $content);
        $this->assertStringContainsString('"Total Quartz 5W-30",41.50', $content);
    }

    public function test_xlsx_export_generates_valid_openxml_archive(): void
    {
        $headers = [
            'name'        => 'Product Name',
            'description' => 'Description',
        ];

        $rows = [
            ['name' => 'Synthetic Motor Oil', 'description' => 'High performance full synthetic oil'],
            ['name' => 'Oil Filter', 'description' => 'OEM filter replacement'],
        ];

        $result = $this->service->generateXlsxExport($headers, $rows, 'Products', 'products_export');

        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $result['mime']);
        $this->assertStringEndsWith('.xlsx', $result['filename']);

        // Verify valid ZIP archive
        $tmp = tempnam(sys_get_temp_dir(), 'test_xlsx_') . '.xlsx';
        file_put_contents($tmp, $result['content']);

        $zip = new ZipArchive();
        $opened = $zip->open($tmp);
        $this->assertTrue($opened);
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
        $this->assertNotFalse($zip->locateName('xl/styles.xml'));

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('Product Name', $sheetXml);
        $this->assertStringContainsString('Synthetic Motor Oil', $sheetXml);
        $this->assertStringContainsString('High performance full synthetic oil', $sheetXml);

        $zip->close();
        @unlink($tmp);
    }

    public function test_pdf_report_generates_valid_pdf_stream(): void
    {
        $headers = [
            'sku'           => 'SKU',
            'name'          => 'Product Name',
            'selling_price' => 'Price',
            'status'        => 'Status',
        ];

        $rows = [
            ['sku' => 'OIL-001', 'name' => 'Engine Oil 5W-30', 'selling_price' => '45.00', 'status' => 'Active'],
            ['sku' => 'FLT-002', 'name' => 'Cabin Air Filter', 'selling_price' => '18.50', 'status' => 'Active'],
        ];

        $report = new PurePdfReport(true, count($headers));
        $pdfContent = $report->build('Catalog Report', $headers, $rows);

        $this->assertStringStartsWith('%PDF-1.4', $pdfContent);
        $this->assertStringContainsString('Catalog Report', $pdfContent);
        $this->assertStringContainsString('Engine Oil 5W-30', $pdfContent);
        $this->assertStringContainsString('%%EOF', $pdfContent);
    }

    public function test_pdf_export_from_service(): void
    {
        $headers = [
            'name'        => 'Product Name',
            'description' => 'Description',
        ];

        $rows = [
            ['name' => 'Product A', 'description' => 'Description A'],
            ['name' => 'Product B', 'description' => 'Description B'],
        ];

        $result = $this->service->generatePdfExport($headers, $rows, 'Catalog', 'test_products');

        $this->assertSame('application/pdf', $result['mime']);
        $this->assertStringStartsWith('test_products_', $result['filename']);
        $this->assertStringEndsWith('.pdf', $result['filename']);
        $this->assertStringStartsWith('%PDF-1.4', $result['content']);
    }
}
