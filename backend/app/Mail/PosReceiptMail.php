<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PosReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $receiptNumber,
        public int $totalAmountCents,
        public string $paymentMethod = 'Cash',
        public ?string $terminalName = null,
        public string $currency = 'ETB',
        public ?string $tenantName = null,
        public ?string $tinNumber = null
    ) {}

    public function build(): self
    {
        $tenant = tenant();
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];
        $tenantName = $this->tenantName ?? ($settings['display_name'] ?? ($tenant?->name ?: config('app.name', 'Bina ERP')));
        $tin = $this->tinNumber ?? ($settings['tax_id'] ?? null);

        return $this->subject("🧾 POS Receipt #{$this->receiptNumber} from {$tenantName}")
            ->view('emails.pos-receipt')
            ->with([
                'receiptNumber'    => $this->receiptNumber,
                'totalAmountCents' => $this->totalAmountCents,
                'paymentMethod'    => $this->paymentMethod,
                'terminalName'     => $this->terminalName,
                'currency'         => $this->currency,
                'tenantName'       => $tenantName,
                'tinNumber'        => $tin,
                'subtitle'         => 'Point of Sale Checkout Receipt',
            ]);
    }
}
