@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">
            {{ $tenantName }}
        </h2>
        @if(!empty($tinNumber))
            <p style="color: #475569; font-size: 12px; font-weight: bold; margin: 0 0 8px 0; font-family: monospace;">TIN: {{ $tinNumber }}</p>
        @endif
        <span class="badge badge-success">PAID & COMPLETED</span>
        <p style="color: #64748b; font-size: 13px; margin: 8px 0 0 0;">Receipt #: <strong>{{ $receiptNumber }}</strong></p>
    </div>

    <div class="card" style="font-size: 13px;">
        <table style="width: 100%;">
            <tr>
                <td style="padding: 3px 0; color: #64748b;">Merchant / Company:</td>
                <td style="padding: 3px 0; font-weight: bold; text-align: right;">{{ $tenantName }}</td>
            </tr>
            @if(!empty($tinNumber))
            <tr>
                <td style="padding: 3px 0; color: #64748b;">Tax ID / TIN:</td>
                <td style="padding: 3px 0; font-weight: bold; text-align: right; font-family: monospace;">{{ $tinNumber }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 3px 0; color: #64748b;">Terminal / Register:</td>
                <td style="padding: 3px 0; font-weight: bold; text-align: right;">{{ $terminalName ?? 'Main Terminal' }}</td>
            </tr>
            <tr>
                <td style="padding: 3px 0; color: #64748b;">Date & Time:</td>
                <td style="padding: 3px 0; font-weight: bold; text-align: right;">{{ now()->format('M d, Y h:i A') }}</td>
            </tr>
            <tr>
                <td style="padding: 3px 0; color: #64748b;">Payment Method:</td>
                <td style="padding: 3px 0; font-weight: bold; text-align: right; text-transform: uppercase;">{{ $paymentMethod ?? 'Cash' }}</td>
            </tr>
            <tr style="border-top: 1px solid #e2e8f0;">
                <td style="padding: 8px 0 0 0; font-weight: 800; font-size: 16px; color: #0f172a;">Total Paid:</td>
                <td style="padding: 8px 0 0 0; font-weight: 800; font-size: 16px; color: #15803d; text-align: right;">
                    {{ number_format(($totalAmountCents ?? 0) / 100, 2) }} {{ $currency ?? 'ETB' }}
                </td>
            </tr>
        </table>
    </div>

    <p style="text-align: center; color: #64748b; font-size: 13px; margin-top: 24px;">
        Thank you for shopping with us! Please retain this receipt for your records or returns.
    </p>
@endsection
