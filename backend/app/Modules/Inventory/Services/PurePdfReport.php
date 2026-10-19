<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

class PurePdfReport
{
    private array $pages = [];
    private string $currentStream = '';
    private float $pageWidth = 842.0; // A4 Landscape default
    private float $pageHeight = 595.0;
    private float $marginTop = 40.0;
    private float $marginBottom = 40.0;
    private float $marginLeft = 32.0;
    private float $marginRight = 32.0;
    private float $y = 0.0;
    private int $pageNumber = 0;
    private bool $isLandscape = true;

    public function __construct(?bool $landscape = null, int $columnCount = 6)
    {
        // Automatically default to landscape if more than 4 columns
        $this->isLandscape = $landscape ?? ($columnCount > 4);

        if ($this->isLandscape) {
            $this->pageWidth = 842.0;
            $this->pageHeight = 595.0;
        } else {
            $this->pageWidth = 595.0;
            $this->pageHeight = 842.0;
        }
    }

    private function addPage(): void
    {
        if ($this->currentStream !== '') {
            $this->pages[] = $this->currentStream;
            $this->currentStream = '';
        }
        $this->pageNumber++;
        $this->y = $this->pageHeight - $this->marginTop;
    }

    private function escape(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $clean = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
        return $clean !== false ? $clean : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    /**
     * Build standard PDF document.
     *
     * @param string $title
     * @param array<string, string> $headers Key => Label
     * @param array<int, array<string, mixed>> $rows
     * @param string|null $subtitle
     * @return string Raw PDF bytes
     */
    public function build(string $title, array $headers, array $rows, ?string $subtitle = null): string
    {
        $this->addPage();

        $printableWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $colKeys = array_keys($headers);
        $colLabels = array_values($headers);
        $colCount = max(1, count($headers));

        // 1. Calculate proportional column widths
        $colWidths = $this->calculateColumnWidths($colKeys, $printableWidth);

        // 2. Draw Title & Meta Banner
        $recordCount = count($rows);
        $sub = $subtitle ?? ("Total Records: {$recordCount} | Generated: " . date('Y-m-d H:i'));
        $this->drawHeaderBanner($title, $sub, $printableWidth);

        // 3. Draw Table Header
        $this->drawTableHeader($colLabels, $colWidths);

        // 4. Draw Rows
        $rowIndex = 0;
        $rowHeight = 19.0;

        foreach ($rows as $row) {
            // Check page overflow
            if ($this->y - $rowHeight < ($this->marginBottom + 10.0)) {
                $this->drawFooter();
                $this->addPage();
                $this->drawTableHeader($colLabels, $colWidths);
            }

            // Extract cells in order of colKeys
            $cellValues = [];
            foreach ($colKeys as $key) {
                $cellValues[] = (string) ($row[$key] ?? '');
            }

            $this->drawRow($cellValues, $colKeys, $colWidths, $rowIndex % 2 === 1, $rowHeight);
            $rowIndex++;
        }

        $this->drawFooter();

        if ($this->currentStream !== '') {
            $this->pages[] = $this->currentStream;
        }

        return $this->compilePdf();
    }

    /**
     * Proportional column width distribution based on column semantics.
     *
     * @param string[] $colKeys
     * @param float $totalWidth
     * @return float[]
     */
    private function calculateColumnWidths(array $colKeys, float $totalWidth): array
    {
        $weights = [
            'description'        => 2.6,
            'name'               => 2.2,
            'category'           => 1.4,
            'sku'                => 1.3,
            'selling_price'      => 1.1,
            'cost_price'         => 1.1,
            'quantity_on_hand'   => 1.0,
            'available_quantity' => 1.0,
            'barcode'            => 1.3,
            'type'               => 0.9,
            'status'             => 0.9,
            'created_at'         => 1.2,
        ];

        $totalWeight = 0.0;
        $assignedWeights = [];
        foreach ($colKeys as $key) {
            $w = $weights[$key] ?? 1.0;
            $assignedWeights[] = $w;
            $totalWeight += $w;
        }

        if ($totalWeight <= 0) {
            $totalWeight = 1.0;
        }

        $widths = [];
        foreach ($assignedWeights as $w) {
            $widths[] = ($w / $totalWeight) * $totalWidth;
        }

        return $widths;
    }

    private function drawHeaderBanner(string $title, string $subtitle, float $width): void
    {
        $x = $this->marginLeft;
        $y = $this->y;

        // Title (Helvetica-Bold 15pt, Slate-900)
        $this->currentStream .= "BT\n/F2 15 Tf\n0.08 0.12 0.2 rg\n{$x} " . ($y - 12) . " Td\n(" . $this->escape($title) . ") Tj\nET\n";

        // Subtitle (Helvetica 8.5pt, Slate-500)
        $subY = $y - 25;
        $this->currentStream .= "BT\n/F1 8.5 Tf\n0.4 0.45 0.52 rg\n{$x} {$subY} Td\n(" . $this->escape($subtitle) . ") Tj\nET\n";

        // Date right-aligned
        $branding = "ERP System Report";
        $brandingX = $this->pageWidth - $this->marginRight - 90;
        $this->currentStream .= "BT\n/F2 8.5 Tf\n0.18 0.35 0.65 rg\n{$brandingX} " . ($y - 12) . " Td\n(" . $this->escape($branding) . ") Tj\nET\n";

        // Divider line
        $this->y -= 34;
        $lineY = $this->y;
        $this->currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n{$x} {$lineY} m " . ($x + $width) . " {$lineY} l S\n";
        $this->y -= 10;
    }

    private function drawTableHeader(array $labels, array $colWidths): void
    {
        $x = $this->marginLeft;
        $height = 20.0;
        $y = $this->y - $height;
        $totalWidth = array_sum($colWidths);

        // Header Background Fill (slate-900 / navy #0F172A)
        $this->currentStream .= "0.06 0.09 0.16 rg\n{$x} {$y} {$totalWidth} {$height} re f\n";

        // Header texts
        $currentX = $x;
        foreach ($labels as $i => $label) {
            $w = $colWidths[$i] ?? 50.0;
            $cellX = $currentX + 5;
            $cellY = $y + 6;
            // Max characters calculation (approx 5.5 pt per char for 8pt font)
            $maxChars = max(4, (int) (($w - 8) / 5.2));
            $truncated = mb_strimwidth($label, 0, $maxChars, '..');
            $this->currentStream .= "BT\n/F2 8 Tf\n1.0 1.0 1.0 rg\n{$cellX} {$cellY} Td\n(" . $this->escape($truncated) . ") Tj\nET\n";
            $currentX += $w;
        }

        $this->y -= $height;
    }

    private function drawRow(array $values, array $keys, array $colWidths, bool $isZebra, float $height): void
    {
        $x = $this->marginLeft;
        $y = $this->y - $height;
        $totalWidth = array_sum($colWidths);

        // Zebra background fill
        if ($isZebra) {
            $this->currentStream .= "0.965 0.975 0.985 rg\n{$x} {$y} {$totalWidth} {$height} re f\n";
        }

        // Bottom border line (#E2E8F0)
        $this->currentStream .= "0.88 0.91 0.94 RG\n0.4 w\n{$x} {$y} m " . ($x + $totalWidth) . " {$y} l S\n";

        // Cell texts
        $currentX = $x;
        foreach ($values as $i => $rawVal) {
            $w = $colWidths[$i] ?? 50.0;
            $key = $keys[$i] ?? '';
            $cellX = $currentX + 5;
            $cellY = $y + 5.5;

            $text = trim($rawVal);

            // Right-align formatting for prices and quantities
            $isNumeric = in_array($key, ['selling_price', 'cost_price', 'quantity_on_hand', 'available_quantity'], true);
            if ($isNumeric && in_array($key, ['selling_price', 'cost_price'], true) && is_numeric($text)) {
                $text = '$ ' . number_format((float) $text, 2);
            }

            $maxChars = max(4, (int) (($w - 8) / 4.8));
            $truncated = mb_strimwidth($text, 0, $maxChars, '..');

            // Text color (slate-800 for data, muted for SKU/dates)
            $r = 0.12; $g = 0.16; $b = 0.22;
            if ($key === 'status') {
                $statusLower = strtolower($text);
                if ($statusLower === 'active') {
                    $r = 0.05; $g = 0.55; $b = 0.25; // emerald
                } elseif ($statusLower === 'inactive') {
                    $r = 0.80; $g = 0.45; $b = 0.10; // amber
                }
            }

            $this->currentStream .= "BT\n/F1 7.5 Tf\n{$r} {$g} {$b} rg\n{$cellX} {$cellY} Td\n(" . $this->escape($truncated) . ") Tj\nET\n";
            $currentX += $w;
        }

        $this->y -= $height;
    }

    private function drawFooter(): void
    {
        $x = $this->marginLeft;
        $y = $this->marginBottom - 14;
        $footerText = "ERP Inventory System • Generated Document • Confidential";
        $pageText = "Page " . $this->pageNumber;
        $pageTextX = $this->pageWidth - $this->marginRight - 45;

        // Top thin rule above footer
        $topRuleY = $this->marginBottom - 3;
        $totalWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $this->currentStream .= "0.88 0.90 0.93 RG\n0.5 w\n{$x} {$topRuleY} m " . ($x + $totalWidth) . " {$topRuleY} l S\n";

        $this->currentStream .= "BT\n/F1 7.5 Tf\n0.45 0.5 0.55 rg\n{$x} {$y} Td\n(" . $this->escape($footerText) . ") Tj\nET\n";
        $this->currentStream .= "BT\n/F2 7.5 Tf\n0.45 0.5 0.55 rg\n{$pageTextX} {$y} Td\n(" . $this->escape($pageText) . ") Tj\nET\n";
    }

    private function compilePdf(): string
    {
        $numPages = count($this->pages);
        if ($numPages === 0) {
            $this->addPage();
            $numPages = 1;
        }

        $body = "%PDF-1.4\n";
        $offsets = [0];

        // 1: Catalog
        $offsets[1] = strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // 2: Pages container
        $kids = [];
        for ($i = 0; $i < $numPages; $i++) {
            $pageObjId = 6 + ($i * 2);
            $kids[] = "{$pageObjId} 0 R";
        }
        $kidsStr = implode(' ', $kids);

        $offsets[2] = strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [{$kidsStr}] /Count {$numPages} >>\nendobj\n";

        // 3: Font Helvetica (Regular)
        $offsets[3] = strlen($body);
        $body .= "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";

        // 4: Font Helvetica-Bold (Bold)
        $offsets[4] = strlen($body);
        $body .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";

        // 5: Shared Resources
        $offsets[5] = strlen($body);
        $body .= "5 0 obj\n<< /Font << /F1 3 0 R /F2 4 0 R >> >>\nendobj\n";

        // Pages and Content Streams
        for ($i = 0; $i < $numPages; $i++) {
            $pageObjId = 6 + ($i * 2);
            $contentObjId = $pageObjId + 1;
            $stream = $this->pages[$i];
            $streamLen = strlen($stream);

            $offsets[$pageObjId] = strlen($body);
            $body .= "{$pageObjId} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Resources 5 0 R /Contents {$contentObjId} 0 R >>\nendobj\n";

            $offsets[$contentObjId] = strlen($body);
            $body .= "{$contentObjId} 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj\n";
        }

        // Xref table
        $xrefOffset = strlen($body);
        $totalObjects = 5 + ($numPages * 2);
        $body .= "xref\n0 " . ($totalObjects + 1) . "\n";
        $body .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $totalObjects; $i++) {
            $offset = $offsets[$i] ?? 0;
            $body .= sprintf("%010d 00000 n \n", $offset);
        }

        // Trailer
        $body .= "trailer\n<< /Size " . ($totalObjects + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $body;
    }
}
