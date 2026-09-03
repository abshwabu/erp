<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobApplicationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $applicantName,
        public string $applicantEmail,
        public string $jobTitle,
        public ?string $tenantName = null
    ) {}

    public function build(): self
    {
        $tenantName = $this->tenantName ?? (tenant('name') ?: config('app.name', 'ERP System'));

        return $this->subject("💼 Application Received: {$this->jobTitle} - {$tenantName}")
            ->view('emails.job-application-confirmation')
            ->with([
                'applicantName'  => $this->applicantName,
                'applicantEmail' => $this->applicantEmail,
                'jobTitle'       => $this->jobTitle,
                'tenantName'     => $tenantName,
                'subtitle'       => 'Recruitment & Talent Acquisition',
            ]);
    }
}
