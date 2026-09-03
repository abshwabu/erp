@extends('emails.layout')

@section('content')
    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 0;">
        ✉️ Email Service Verification Test
    </h2>
    <p style="color: #475569; font-size: 14px;">
        Hello <strong>{{ $recipientName ?? 'Administrator' }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        This test message confirms that your ERP email delivery system is functioning with full end-to-end SMTP connectivity.
    </p>

    <div class="card">
        <h4 style="margin: 0 0 12px 0; font-size: 13px; text-transform: uppercase; color: #64748b; font-weight: 800;">
            Diagnostic Telemetry
        </h4>
        <table style="width: 100%; font-size: 13px; color: #334155;">
            <tr>
                <td style="padding: 4px 0; font-weight: bold; width: 40%;">Timestamp:</td>
                <td style="padding: 4px 0; font-family: monospace;">{{ now()->toDateTimeString() }} UTC</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; font-weight: bold;">Mail Driver:</td>
                <td style="padding: 4px 0; font-family: monospace;">{{ config('mail.default', 'smtp') }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; font-weight: bold;">SMTP Host:</td>
                <td style="padding: 4px 0; font-family: monospace;">{{ config('mail.mailers.smtp.host', 'localhost') }}:{{ config('mail.mailers.smtp.port', '1025') }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; font-weight: bold;">Tenant Context:</td>
                <td style="padding: 4px 0; font-weight: bold; color: #4f46e5;">{{ $tenantName ?? 'Default Platform' }}</td>
            </tr>
        </table>
    </div>

    <p style="color: #64748b; font-size: 12px; margin-bottom: 0;">
        No further action is required. All outgoing transactional notifications, customer invoices, and security verification codes will be delivered through this active transport.
    </p>
@endsection
