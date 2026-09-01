<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Services;

use App\Modules\Integrations\Models\Integration;
use App\Modules\Integrations\Models\IntegrationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntegrationDispatcherService
{
    /**
     * Dispatch an ERP event across all connected and subscribed integrations.
     *
     * @param string $event (e.g. 'invoice.paid', 'lead.captured', 'stock.low_warning', 'ticket.created')
     * @param array $payload
     * @return int Number of integrations that received the dispatch
     */
    public static function dispatch(string $event, array $payload): int
    {
        try {
            $integrations = Integration::where('status', 'connected')->get();
            if ($integrations->isEmpty()) {
                return 0;
            }

            $dispatchedCount = 0;

            foreach ($integrations as $integration) {
                if (!self::isSubscribed($integration, $event)) {
                    continue;
                }

                self::sendToIntegration($integration, $event, $payload);
                $dispatchedCount++;
            }

            return $dispatchedCount;
        } catch (\Throwable $e) {
            Log::warning("IntegrationDispatcherService dispatch error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Determine if an integration is subscribed to this event topic.
     */
    private static function isSubscribed(Integration $integration, string $event): bool
    {
        $settings = is_array($integration->settings) ? $integration->settings : [];
        $subscribedEvents = $settings['events'] ?? [];

        // If no events specified or wildcard '*', accept all
        if (empty($subscribedEvents) || in_array('*', $subscribedEvents, true) || in_array('* (All System Events)', $subscribedEvents, true)) {
            return true;
        }

        if (in_array($event, $subscribedEvents, true)) {
            return true;
        }

        // Check category prefix wildcard (e.g. 'invoice.*')
        $prefix = explode('.', $event)[0] ?? '';
        if (!empty($prefix) && (in_array($prefix . '.*', $subscribedEvents, true) || in_array($prefix, $subscribedEvents, true))) {
            return true;
        }

        return false;
    }

    /**
     * Format and transmit payload to the integration target.
     */
    private static function sendToIntegration(Integration $integration, string $event, array $payload): void
    {
        $statusCode = 200;
        $responseBody = [
            'dispatched' => true,
            'timestamp'  => now()->toIso8601String(),
        ];

        $targetUrl = $integration->webhook_url;
        $settings = is_array($integration->settings) ? $integration->settings : [];

        // Prepare outbound HTTP body based on provider type
        $outboundBody = match ($integration->provider) {
            'slack' => [
                'text' => self::formatSlackMessage($event, $payload),
                'channel' => $settings['channel'] ?? null,
                'erp_event' => $event,
                'data' => $payload,
            ],
            'telegram' => [
                'chat_id' => $settings['chat_id'] ?? null,
                'text' => self::formatTelegramMessage($event, $payload),
                'parse_mode' => 'Markdown',
            ],
            default => [
                'event'       => $event,
                'timestamp'   => now()->toIso8601String(),
                'provider'    => $integration->provider,
                'integration' => $integration->name,
                'data'        => $payload,
            ],
        };

        // If a valid live webhook URL is configured, perform HTTP request
        if (!empty($targetUrl) && filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            try {
                $headers = [
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'ERP-Enterprise-Webhook-Engine/1.0',
                    'X-ERP-Event'  => $event,
                ];

                if (!empty($settings['signing_secret'])) {
                    $signature = hash_hmac('sha256', json_encode($outboundBody), (string) $settings['signing_secret']);
                    $headers['X-ERP-Signature'] = 'sha256=' . $signature;
                }

                if (!empty($integration->api_key)) {
                    $headers['Authorization'] = 'Bearer ' . $integration->api_key;
                }

                $start = microtime(true);
                $resp = Http::withHeaders($headers)
                    ->timeout(4)
                    ->post($targetUrl, $outboundBody);

                $latency = (int) round((microtime(true) - $start) * 1000);
                $statusCode = $resp->status();
                $responseBody = [
                    'transmitted' => true,
                    'latency_ms'  => $latency,
                    'status_code' => $statusCode,
                    'body'        => $resp->json() ?? Str::limit($resp->body(), 500),
                ];
            } catch (\Throwable $e) {
                $statusCode = 502;
                $responseBody = [
                    'transmitted' => false,
                    'error'       => $e->getMessage(),
                ];
            }
        } else {
            // Internal gateway dispatch recording
            $responseBody['mock_delivery'] = true;
            $responseBody['note'] = 'Connector active - simulated dispatch recorded.';
        }

        // Record in integration_logs table
        try {
            IntegrationLog::create([
                'integration_id' => $integration->id,
                'event'          => $event,
                'direction'      => 'outbound',
                'status_code'    => $statusCode,
                'payload'        => $outboundBody,
                'response'       => $responseBody,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to write IntegrationLog: " . $e->getMessage());
        }
    }

    private static function formatSlackMessage(string $event, array $payload): string
    {
        return match ($event) {
            'invoice.paid' => "💰 *Invoice Paid*: Invoice `{$payload['invoice_number']}` for *{$payload['amount']}* ({$payload['customer']}) was successfully settled.",
            'invoice.created' => "📄 *New Invoice Issued*: Invoice `{$payload['invoice_number']}` for *{$payload['amount']}* to *{$payload['customer']}*.",
            'lead.captured' => "🎯 *New Lead Captured*: *{$payload['name']}* ({$payload['email']}) from source _{$payload['source']}_.",
            'deal.won' => "🎉 *Deal Closed / Won*: Deal *{$payload['title']}* worth *{$payload['value']}* won by *{$payload['owner']}*!",
            'ticket.created' => "🎫 *New Support Ticket*: [#{$payload['ticket_number']}] *{$payload['subject']}* (Priority: {$payload['priority']}).",
            'stock.low_warning' => "⚠️ *Low Stock Alert*: Product *{$payload['product_name']}* (`{$payload['sku']}`) has only *{$payload['current_stock']}* units remaining.",
            'payroll.approved' => "💼 *Payroll Run Approved*: Period `{$payload['period']}` approved with total disbursements of *{$payload['total_amount']}*.",
            default => "🔔 *ERP Event Notification*: `{$event}` occurred at " . now()->toDateTimeString(),
        };
    }

    private static function formatTelegramMessage(string $event, array $payload): string
    {
        return match ($event) {
            'invoice.paid' => "💰 *Invoice Paid*\n• Invoice: `{$payload['invoice_number']}`\n• Customer: {$payload['customer']}\n• Total: *{$payload['amount']}*",
            'lead.captured' => "🎯 *New Lead Captured*\n• Name: {$payload['name']}\n• Email: `{$payload['email']}`\n• Source: {$payload['source']}",
            'stock.low_warning' => "⚠️ *Low Stock Alert*\n• Product: {$payload['product_name']}\n• SKU: `{$payload['sku']}`\n• Stock: {$payload['current_stock']}",
            default => "🔔 *ERP Event*: `{$event}`",
        };
    }
}
