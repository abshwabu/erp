<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $user,
        public ?string $temporaryPassword = null,
        public ?string $roleName = null,
        public ?string $tenantName = null,
        public ?string $loginUrl = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));

        return $this->subject("👋 Welcome to {$tenantName} - Your ERP Account Access")
            ->view('emails.user-invitation')
            ->with([
                'user'              => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
                'roleName'          => $this->roleName,
                'tenantName'        => $tenantName,
                'loginUrl'          => $this->loginUrl ?? url('/login'),
                'subtitle'          => 'Team Invitation',
            ]);
    }
}
