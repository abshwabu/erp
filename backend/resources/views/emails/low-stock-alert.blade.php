@extends('emails.layout')

@section('content')
    <h2 style="font-size: 20px; font-weight: 800; color: #dc2626; margin-top: 0;">
        ⚠️ Low Stock & Reorder Alert
    </h2>
    <p style="color: #475569; font-size: 14px;">
        Attention Inventory Manager,
    </p>
    <p style="color: #475569; font-size: 14px;">
        The following items in your catalog have fallen below their safety reorder thresholds:
    </p>

    @if(!empty($items) && count($items) > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Product / SKU</th>
                <th style="text-align: center;">On Hand</th>
                <th style="text-align: center;">Min Reorder</th>
                <th style="text-align: right;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td><strong>{{ $item['name'] ?? $item->name }}</strong><br><span style="font-size: 11px; color: #64748b;">SKU: {{ $item['sku'] ?? $item->sku }}</span></td>
                <td style="text-align: center; color: #dc2626; font-weight: bold;">{{ $item['quantity'] ?? $item->quantity ?? 0 }}</td>
                <td style="text-align: center;">{{ $item['min_stock'] ?? $item->min_stock ?? 5 }}</td>
                <td style="text-align: right;"><span class="badge badge-warning">REORDER</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div style="text-align: center; margin: 24px 0;">
        <a href="{{ url('/inventory/products') }}" class="button">
            Open Inventory Dashboard
        </a>
    </div>
@endsection
