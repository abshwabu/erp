<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $order,
        public array $items = [],
        public string $currency = 'ETB',
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));
        $poNum = $this->order->po_number ?? $this->order->id;

        return $this->subject("📦 Purchase Order #{$poNum} from {$tenantName}")
            ->view('emails.purchase-order')
            ->with([
                'order'      => $this->order,
                'items'      => $this->items,
                'currency'   => $this->currency,
                'tenantName' => $tenantName,
                'subtitle'   => 'Procurement & Purchasing Order',
            ]);
    }
}
