<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $invoice,
        public array $items = [],
        public string $currency = 'ETB',
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));
        $invNum = $this->invoice->invoice_number ?? $this->invoice->id;

        return $this->subject("📄 Invoice #{$invNum} from {$tenantName}")
            ->view('emails.invoice')
            ->with([
                'invoice'    => $this->invoice,
                'items'      => $this->items,
                'currency'   => $this->currency,
                'tenantName' => $tenantName,
                'subtitle'   => 'Customer Invoicing & Billing',
            ]);
    }
}
