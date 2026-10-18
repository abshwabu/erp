<?php

declare(strict_types=1);

namespace App\Modules\POS\Services;

use App\Modules\POS\Models\POSTransaction;
use App\Modules\Sales\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerTinLookupService
{
    /**
     * Look up a customer by Tax Identification Number (TIN).
     *
     * Searches:
     * 1. Existing local Customers directory
     * 2. Historical POS transactions matching this TIN
     * 3. Configured Ethiopian eTrade / Ministry of Trade / Tax Authority registry API (if configured)
     *
     * @return array{found: bool, source: string|null, tin: string, name: string|null, customer_id?: string|null, phone?: string|null, message?: string}
     */
    public function lookup(string $rawTin): array
    {
        $cleaned = trim(preg_replace('/[^0-9A-Za-z]/', '', $rawTin) ?? '');

        if ($cleaned === '') {
            return [
                'found' => false,
                'source' => null,
                'tin' => $rawTin,
                'name' => null,
                'message' => 'Please provide a valid TIN.',
            ];
        }

        // Standard Ethiopian TIN format is 10 digits; if 9 digits, pad leading zero as fallback candidate
        $candidates = array_unique(array_filter([
            $cleaned,
            $rawTin,
            (strlen($cleaned) === 9 && ctype_digit($cleaned)) ? '0' . $cleaned : null,
        ]));

        // 1. Look up in existing Customers directory
        $customer = Customer::query()
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $cand) {
                    $query->orWhere('tin', $cand);
                }
            })
            ->first();

        if ($customer) {
            return [
                'found' => true,
                'source' => 'local_customer',
                'tin' => $customer->tin ?: $cleaned,
                'name' => $customer->name,
                'customer_id' => $customer->id,
                'phone' => $customer->phone,
                'email' => $customer->email,
            ];
        }

        // 2. Look up in previous POS transactions
        $previousSale = POSTransaction::query()
            ->whereNotNull('customer_name')
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $cand) {
                    $query->orWhere('customer_tin', $cand);
                }
            })
            ->latest('created_at')
            ->first();

        if ($previousSale && !empty(trim((string) $previousSale->customer_name))) {
            return [
                'found' => true,
                'source' => 'previous_sale',
                'tin' => $previousSale->customer_tin ?: $cleaned,
                'name' => trim((string) $previousSale->customer_name),
                'customer_id' => $previousSale->customer_id,
            ];
        }

        // 3. Query Ethiopian eTrade / MoTRI / Tax Authority API if configured
        $externalResult = $this->queryExternalETradeApi($cleaned, $candidates);
        if ($externalResult !== null) {
            return $externalResult;
        }

        // Not found - user can enter customer name manually
        return [
            'found' => false,
            'source' => null,
            'tin' => $cleaned,
            'name' => null,
            'message' => 'Customer name not found for this TIN in eTrade/records. You can enter the customer name manually.',
        ];
    }

    /**
     * Query configured eTrade or Tax Authority API.
     */
    protected function queryExternalETradeApi(string $cleanedTin, array $candidates): ?array
    {
        $tenant = function_exists('tenant') ? tenant() : null;
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];

        $apiUrl = $settings['etrade_api_url']
            ?? config('services.etrade.url')
            ?? env('ETRADE_API_URL');

        if (!$apiUrl) {
            return null;
        }

        $apiKey = $settings['etrade_api_key']
            ?? config('services.etrade.key')
            ?? env('ETRADE_API_KEY');

        try {
            $headers = array_filter([
                'Accept' => 'application/json',
                'Authorization' => $apiKey ? 'Bearer ' . $apiKey : null,
                'X-API-Key' => $apiKey,
            ]);

            $response = Http::timeout(3)
                ->withHeaders($headers)
                ->get($apiUrl, ['tin' => $cleanedTin]);

            if ($response->successful()) {
                $payload = $response->json();
                $name = $payload['trade_name']
                    ?? $payload['business_name']
                    ?? $payload['customer_name']
                    ?? $payload['taxpayer_name']
                    ?? $payload['name']
                    ?? $payload['data']['trade_name']
                    ?? $payload['data']['name']
                    ?? null;

                if (!empty($name) && is_string($name)) {
                    return [
                        'found' => true,
                        'source' => 'etrade_api',
                        'tin' => $cleanedTin,
                        'name' => trim($name),
                        'details' => is_array($payload) ? $payload : [],
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info('eTrade TIN lookup error: ' . $e->getMessage(), ['tin' => $cleanedTin]);
        }

        return null;
    }
}
