<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName = 'Administrator',
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));

        return $this->subject("✅ Email Service Test - {$tenantName}")
            ->view('emails.test')
            ->with([
                'recipientName' => $this->recipientName,
                'tenantName'    => $tenantName,
                'subtitle'      => 'Email Verification System',
            ]);
    }
}
