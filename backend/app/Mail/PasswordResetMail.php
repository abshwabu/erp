<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $user,
        public string $resetUrl,
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));

        return $this->subject("🔐 Reset Your Account Password - {$tenantName}")
            ->view('emails.password-reset')
            ->with([
                'user'       => $this->user,
                'resetUrl'   => $this->resetUrl,
                'tenantName' => $tenantName,
                'subtitle'   => 'Security & Authentication',
            ]);
    }
}
