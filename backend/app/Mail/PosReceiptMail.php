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
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));

        return $this->subject("🧾 POS Receipt #{$this->receiptNumber} from {$tenantName}")
            ->view('emails.pos-receipt')
            ->with([
                'receiptNumber'    => $this->receiptNumber,
                'totalAmountCents' => $this->totalAmountCents,
                'paymentMethod'    => $this->paymentMethod,
                'terminalName'     => $this->terminalName,
                'currency'         => $this->currency,
                'tenantName'       => $tenantName,
                'subtitle'         => 'Point of Sale Checkout Receipt',
            ]);
    }
}
