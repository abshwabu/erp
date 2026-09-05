<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Mail\InvoiceMail;
use App\Mail\JobApplicationConfirmationMail;
use App\Mail\LowStockAlertMail;
use App\Mail\MfaCodeMail;
use App\Mail\PasswordResetMail;
use App\Mail\PosReceiptMail;
use App\Mail\PurchaseOrderMail;
use App\Mail\TestMail;
use App\Mail\TicketNotificationMail;
use App\Mail\UserInvitationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send diagnostic test email and benchmark connection speed.
     */
    public static function sendTestEmail(string $recipientEmail, string $recipientName = 'Administrator'): array
    {
        $start = microtime(true);
        $tenantName = tenant('name') ?: config('app.name', 'ERP System');

        try {
            Mail::to($recipientEmail, $recipientName)
                ->send(new TestMail($recipientName, $tenantName));

            $durationMs = round((microtime(true) - $start) * 1000, 2);

            return [
                'success'     => true,
                'recipient'   => $recipientEmail,
                'duration_ms' => $durationMs,
                'message'     => "Test email successfully delivered to {$recipientEmail} in {$durationMs}ms.",
            ];
        } catch (\Throwable $e) {
            Log::error("[EmailService] Failed to send test email to {$recipientEmail}: " . $e->getMessage());

            return [
                'success'     => false,
                'recipient'   => $recipientEmail,
                'error'       => $e->getMessage(),
                'message'     => "Email delivery failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Send password reset email.
     */
    public static function sendPasswordReset(object $user, string $token): bool
    {
        try {
            $frontendUrl = config('app.frontend_url', url('/'));
            $resetUrl = rtrim($frontendUrl, '/') . "/reset-password?token={$token}&email=" . urlencode($user->email);
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');

            Mail::to($user->email, $user->name)
                ->send(new PasswordResetMail($user, $resetUrl, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] Password reset email failed for {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send 6-digit MFA security verification code.
     */
    public static function sendMfaCode(object $user, string $code): bool
    {
        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');

            Mail::to($user->email, $user->name)
                ->send(new MfaCodeMail($user, $code, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] MFA code email failed for {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send team invitation / new user onboarding email.
     */
    public static function sendUserInvitation(
        object $user,
        ?string $temporaryPassword = null,
        ?string $roleName = null
    ): bool {
        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');
            $loginUrl = rtrim(config('app.frontend_url', url('/')), '/') . '/login';

            Mail::to($user->email, $user->name)
                ->send(new UserInvitationMail($user, $temporaryPassword, $roleName, $tenantName, $loginUrl));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] User invitation email failed for {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send customer invoice email.
     */
    public static function sendInvoice(object $invoice, ?string $recipientEmail = null, array $items = []): bool
    {
        $to = $recipientEmail ?? $invoice->customer_email ?? null;
        if (!$to) {
            return false;
        }

        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');
            $currency = tenant('currency') ?: 'ETB';

            Mail::to($to, $invoice->customer_name ?? 'Customer')
                ->send(new InvoiceMail($invoice, $items, $currency, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] Invoice email failed for {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send POS retail checkout digital receipt email.
     */
    public static function sendPosReceipt(
        string $recipientEmail,
        string $receiptNumber,
        int $totalAmountCents,
        string $paymentMethod = 'Cash',
        ?string $terminalName = null
    ): bool {
        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');
            $currency = tenant('currency') ?: 'ETB';

            Mail::to($recipientEmail)
                ->send(new PosReceiptMail($receiptNumber, $totalAmountCents, $paymentMethod, $terminalName, $currency, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] POS receipt email failed for {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send supplier purchase order email.
     */
    public static function sendPurchaseOrder(object $order, ?string $recipientEmail = null, array $items = []): bool
    {
        $to = $recipientEmail ?? $order->vendor_email ?? null;
        if (!$to) {
            return false;
        }

        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');
            $currency = tenant('currency') ?: 'ETB';

            Mail::to($to, $order->vendor_name ?? 'Vendor')
                ->send(new PurchaseOrderMail($order, $items, $currency, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] Purchase order email failed for {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send support ticket notification email.
     */
    public static function sendTicketNotification(
        object $ticket,
        string $recipientEmail,
        string $recipientName = 'Customer',
        ?string $messageBody = null
    ): bool {
        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');

            Mail::to($recipientEmail, $recipientName)
                ->send(new TicketNotificationMail($ticket, $recipientName, $messageBody, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] Ticket notification email failed for {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send job application confirmation email.
     */
    public static function sendJobApplicationConfirmation(
        string $applicantName,
        string $applicantEmail,
        string $jobTitle
    ): bool {
        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');

            Mail::to($applicantEmail, $applicantName)
                ->send(new JobApplicationConfirmationMail($applicantName, $applicantEmail, $jobTitle, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] Job application confirmation email failed for {$applicantEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send low stock reorder alert to inventory manager.
     */
    public static function sendLowStockAlert(array $items, ?string $recipientEmail = null): bool
    {
        $to = $recipientEmail ?? config('mail.from.address', 'admin@example.com');

        try {
            $tenantName = tenant('name') ?: config('app.name', 'ERP System');

            Mail::to($to)
                ->send(new LowStockAlertMail($items, $tenantName));

            return true;
        } catch (\Throwable $e) {
            Log::error("[EmailService] Low stock alert email failed for {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check active mail transport connectivity.
     */
    public static function getDiagnostics(): array
    {
        $driver = config('mail.default', 'smtp');
        $host = config('mail.mailers.smtp.host', '127.0.0.1');
        $port = (int) config('mail.mailers.smtp.port', 1025);
        $fromAddress = config('mail.from.address', 'noreply@erp.local');
        $fromName = config('mail.from.name', 'ERP System');

        $isReachable = false;
        $pingMs = 0;
        $connectionError = null;

        if ($driver === 'smtp') {
            $start = microtime(true);
            $fp = @fsockopen($host, $port, $errno, $errstr, 2.0);
            if ($fp) {
                $isReachable = true;
                $pingMs = round((microtime(true) - $start) * 1000, 2);
                fclose($fp);
            } else {
                $connectionError = "Could not connect to SMTP host {$host}:{$port} ({$errstr})";
            }
        } else {
            $isReachable = true;
        }

        $mailhogUiUrl = env('MAILHOG_UI_URL') ?: env('MAIL_UI_URL');
        if (! $mailhogUiUrl) {
            $requestHost = request()?->getHost();
            if ($requestHost && $requestHost !== '127.0.0.1' && $requestHost !== 'localhost') {
                $scheme = request()?->getScheme() ?: 'http';
                $mailhogUiUrl = "{$scheme}://{$requestHost}:8025";
            } else {
                $appUrl = config('app.url', 'http://localhost');
                $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
                $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
                $mailhogUiUrl = "{$scheme}://{$host}:8025";
            }
        }

        return [
            'driver'           => $driver,
            'smtp_host'        => $host,
            'smtp_port'        => $port,
            'from_address'     => $fromAddress,
            'from_name'        => $fromName,
            'is_connected'     => $isReachable,
            'latency_ms'       => $pingMs,
            'connection_error' => $connectionError,
            'mailhog_ui_url'   => $mailhogUiUrl,
        ];
    }
}
