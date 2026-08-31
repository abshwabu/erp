<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseController
{
    public function show(): JsonResponse
    {
        $tenant = tenant();
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];

        return $this->successResponse([
            // Company Profile
            'display_name' => $settings['display_name'] ?? ($tenant?->name ?? ''),
            'company_email' => $settings['company_email'] ?? '',
            'company_phone' => $settings['company_phone'] ?? '',
            'company_address' => $settings['company_address'] ?? '',
            'tax_id' => $settings['tax_id'] ?? '',
            'website' => $settings['website'] ?? '',
            'logo_url' => $settings['logo_url'] ?? '',

            // Localization & Financial
            'timezone' => $settings['timezone'] ?? 'UTC',
            'currency' => $settings['currency'] ?? 'USD',
            'currency_symbol' => $settings['currency_symbol'] ?? '$',
            'date_format' => $settings['date_format'] ?? 'YYYY-MM-DD',
            'fiscal_year_start' => $settings['fiscal_year_start'] ?? 'January',
            'default_tax_rate' => $settings['default_tax_rate'] ?? 0,

            // Invoicing & Numbering Prefixes
            'invoice_prefix' => $settings['invoice_prefix'] ?? 'INV-',
            'quote_prefix' => $settings['quote_prefix'] ?? 'QTE-',
            'po_prefix' => $settings['po_prefix'] ?? 'PO-',
            'default_payment_terms' => $settings['default_payment_terms'] ?? 'Net 30',
            'auto_inventory_sync' => (bool) ($settings['auto_inventory_sync'] ?? true),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:500'],

            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'max:8'],
            'currency_symbol' => ['nullable', 'string', 'max:8'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'fiscal_year_start' => ['nullable', 'string', 'max:20'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'quote_prefix' => ['nullable', 'string', 'max:20'],
            'po_prefix' => ['nullable', 'string', 'max:20'],
            'default_payment_terms' => ['nullable', 'string', 'max:50'],
            'auto_inventory_sync' => ['nullable', 'boolean'],
        ]);

        $tenant = tenant();
        if (! $tenant) {
            return $this->errorResponse('No active tenant.', 400);
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $settings = array_merge($settings, array_filter($validated, static fn ($v) => $v !== null));

        $tenant->update(['settings' => $settings]);

        return $this->show();
    }
}
