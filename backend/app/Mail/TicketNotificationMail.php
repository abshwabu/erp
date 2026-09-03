<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $ticket,
        public string $recipientName = 'Customer',
        public ?string $messageBody = null,
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));

        return $this->subject("🎫 Support Ticket #{$this->ticket->id} - {$this->ticket->subject}")
            ->view('emails.ticket-notification')
            ->with([
                'ticket'        => $this->ticket,
                'recipientName' => $this->recipientName,
                'messageBody'   => $this->messageBody,
                'tenantName'    => $tenantName,
                'subtitle'      => 'Customer Support & Helpdesk',
            ]);
    }
}
