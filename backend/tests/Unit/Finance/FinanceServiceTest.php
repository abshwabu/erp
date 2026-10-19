<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Modules\Finance\Services\FinanceService;
use PHPUnit\Framework\TestCase;

class FinanceServiceTest extends TestCase
{
    private FinanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinanceService();
    }

    public function test_default_categories_structure(): void
    {
        $categories = FinanceService::DEFAULT_CATEGORIES;
        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);

        $types = array_unique(array_column($categories, 'type'));
        $this->assertContains('income', $types);
        $this->assertContains('expense', $types);

        foreach ($categories as $cat) {
            $this->assertArrayHasKey('name', $cat);
            $this->assertArrayHasKey('type', $cat);
            $this->assertArrayHasKey('color', $cat);
            $this->assertArrayHasKey('description', $cat);
            $this->assertArrayHasKey('account_code', $cat);
        }
    }

    public function test_csv_export_structure(): void
    {
        $headers = [
            'transaction_number' => 'Txn #',
            'type'               => 'Type',
            'amount'             => 'Amount ($)',
            'description'        => 'Description',
        ];

        $rows = [
            ['transaction_number' => 'INC-001', 'type' => 'Income', 'amount' => '500.00', 'description' => 'Consulting Payment'],
            ['transaction_number' => 'EXP-002', 'type' => 'Expense', 'amount' => '120.00', 'description' => 'Office Supplies'],
        ];

        $result = $this->service->generateExport($headers, $rows, 'csv');

        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('mime', $result);
        $this->assertSame('text/csv; charset=UTF-8', $result['mime']);
        $this->assertStringStartsWith('finance_transactions_', $result['filename']);
        $this->assertStringEndsWith('.csv', $result['filename']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $result['content']);
        $this->assertStringContainsString('Txn #', $result['content']);
        $this->assertStringContainsString('500.00', $result['content']);
        $this->assertStringContainsString('Consulting Payment', $result['content']);
    }

    public function test_pdf_export_structure(): void
    {
        $headers = [
            'transaction_number' => 'Txn #',
            'type'               => 'Type',
            'amount'             => 'Amount ($)',
            'description'        => 'Description',
        ];

        $rows = [
            ['transaction_number' => 'INC-001', 'type' => 'Income', 'amount' => '500.00', 'description' => 'Consulting Payment'],
        ];

        $result = $this->service->generateExport($headers, $rows, 'pdf');

        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('mime', $result);
        $this->assertSame('application/pdf', $result['mime']);
        $this->assertStringStartsWith('finance_transactions_', $result['filename']);
        $this->assertStringEndsWith('.pdf', $result['filename']);
        $this->assertStringStartsWith('%PDF-1.4', $result['content']);
    }

    public function test_xlsx_export_structure(): void
    {
        $headers = [
            'transaction_number' => 'Txn #',
            'type'               => 'Type',
            'amount'             => 'Amount ($)',
            'description'        => 'Description',
        ];

        $rows = [
            ['transaction_number' => 'INC-001', 'type' => 'Income', 'amount' => '500.00', 'description' => 'Consulting Payment'],
        ];

        $result = $this->service->generateExport($headers, $rows, 'xlsx');

        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('mime', $result);
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $result['mime']);
        $this->assertStringStartsWith('finance_transactions_', $result['filename']);
        $this->assertStringEndsWith('.xlsx', $result['filename']);
        $this->assertNotEmpty($result['content']);
    }
}
