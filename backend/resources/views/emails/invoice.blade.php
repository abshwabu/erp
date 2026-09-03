@extends('emails.layout')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">
            📄 Invoice #{{ $invoice->invoice_number }}
        </h2>
        <span class="badge {{ $invoice->status === 'paid' ? 'badge-success' : 'badge-warning' }}" style="float: right;">
            {{ strtoupper($invoice->status) }}
        </span>
    </div>

    <p style="color: #475569; font-size: 14px;">
        Dear <strong>{{ $invoice->customer_name ?? 'Valued Customer' }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        Please find attached the details for invoice <strong>#{{ $invoice->invoice_number }}</strong> issued on {{ \Carbon\Carbon::parse($invoice->issue_date)->format('M d, Y') }}.
    </p>

    <!-- Summary Box -->
    <div class="card">
        <table style="width: 100%; font-size: 13px;">
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Due Date:</td>
                <td style="padding: 4px 0; font-weight: bold; text-align: right;">{{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Subtotal:</td>
                <td style="padding: 4px 0; text-align: right;">{{ number_format(($invoice->subtotal_cents ?? $invoice->total_amount_cents ?? 0) / 100, 2) }} {{ $currency ?? 'ETB' }}</td>
            </tr>
            @if(!empty($invoice->tax_amount_cents))
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Tax (VAT):</td>
                <td style="padding: 4px 0; text-align: right;">{{ number_format($invoice->tax_amount_cents / 100, 2) }} {{ $currency ?? 'ETB' }}</td>
            </tr>
            @endif
            <tr style="border-top: 1px solid #e2e8f0;">
                <td style="padding: 8px 0 0 0; font-weight: 800; font-size: 15px; color: #0f172a;">Total Balance:</td>
                <td style="padding: 8px 0 0 0; font-weight: 800; font-size: 15px; color: #4f46e5; text-align: right;">
                    {{ number_format(($invoice->total_amount_cents ?? 0) / 100, 2) }} {{ $currency ?? 'ETB' }}
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($items) && count($items) > 0)
    <h4 style="font-size: 13px; text-transform: uppercase; color: #64748b; margin: 24px 0 8px 0;">Itemized Breakdown</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td><strong>{{ $item->description ?? $item['description'] ?? 'Item' }}</strong></td>
                <td style="text-align: center;">{{ $item->quantity ?? $item['quantity'] ?? 1 }}</td>
                <td style="text-align: right;">{{ number_format(($item->unit_price_cents ?? $item['unit_price_cents'] ?? 0) / 100, 2) }}</td>
                <td style="text-align: right;">{{ number_format(($item->total_price_cents ?? $item['total_price_cents'] ?? 0) / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="color: #64748b; font-size: 12px; margin-top: 24px;">
        Thank you for your business! If you have any inquiries regarding this invoice, please contact us.
    </p>
@endsection
