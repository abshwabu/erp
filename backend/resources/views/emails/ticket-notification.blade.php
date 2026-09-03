@extends('emails.layout')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">
            🎫 Support Ticket #{{ $ticket->id }}
        </h2>
        <span class="badge badge-indigo" style="float: right;">
            {{ strtoupper($ticket->status ?? 'OPEN') }}
        </span>
    </div>

    <p style="color: #475569; font-size: 14px;">
        Hello <strong>{{ $recipientName ?? 'Customer' }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        {{ $messageBody ?? 'Your support ticket has been received and our engineering and support specialists are reviewing the details.' }}
    </p>

    <div class="card">
        <p style="margin: 0 0 6px 0; font-size: 13px;"><strong>Subject:</strong> {{ $ticket->subject ?? 'Inquiry' }}</p>
        <p style="margin: 0 0 6px 0; font-size: 13px;"><strong>Priority:</strong> <span style="text-transform: uppercase;">{{ $ticket->priority ?? 'NORMAL' }}</span></p>
        <p style="margin: 0; font-size: 13px;"><strong>Created Date:</strong> {{ now()->format('M d, Y h:i A') }}</p>
    </div>

    <p style="color: #64748b; font-size: 12px; margin-top: 24px;">
        You can reply directly to this notification to update your ticket case.
    </p>
@endsection
