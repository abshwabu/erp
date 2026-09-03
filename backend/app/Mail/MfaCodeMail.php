<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MfaCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $user,
        public string $code,
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));

        return $this->subject("🛡️ Your Security Verification Code: {$this->code} - {$tenantName}")
            ->view('emails.mfa-code')
            ->with([
                'user'       => $this->user,
                'code'       => $this->code,
                'tenantName' => $tenantName,
                'subtitle'   => 'Two-Factor Authentication',
            ]);
    }
}
