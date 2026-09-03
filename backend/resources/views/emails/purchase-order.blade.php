@extends('emails.layout')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">
            📦 Purchase Order #{{ $order->po_number ?? $order->id }}
        </h2>
        <span class="badge badge-indigo" style="float: right;">
            {{ strtoupper($order->status ?? 'SUBMITTED') }}
        </span>
    </div>

    <p style="color: #475569; font-size: 14px;">
        Dear <strong>{{ $order->vendor_name ?? 'Supplier / Vendor' }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        Please find our official Purchase Order details below. Kindly acknowledge receipt and provide the estimated fulfillment and delivery schedule.
    </p>

    <div class="card">
        <table style="width: 100%; font-size: 13px;">
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Order Date:</td>
                <td style="padding: 4px 0; font-weight: bold; text-align: right;">{{ \Carbon\Carbon::parse($order->order_date ?? now())->format('M d, Y') }}</td>
            </tr>
            @if(!empty($order->expected_delivery_date))
            <tr>
                <td style="padding: 4px 0; color: #64748b;">Expected Delivery:</td>
                <td style="padding: 4px 0; font-weight: bold; text-align: right;">{{ \Carbon\Carbon::parse($order->expected_delivery_date)->format('M d, Y') }}</td>
            </tr>
            @endif
            <tr style="border-top: 1px solid #e2e8f0;">
                <td style="padding: 8px 0 0 0; font-weight: 800; font-size: 15px; color: #0f172a;">Total PO Value:</td>
                <td style="padding: 8px 0 0 0; font-weight: 800; font-size: 15px; color: #4f46e5; text-align: right;">
                    {{ number_format(($order->total_amount_cents ?? 0) / 100, 2) }} {{ $currency ?? 'ETB' }}
                </td>
            </tr>
        </table>
    </div>

    <p style="color: #64748b; font-size: 12px; margin-top: 24px;">
        Please include PO #{{ $order->po_number ?? $order->id }} on all shipping labels, packing slips, and billing invoices.
    </p>
@endsection
