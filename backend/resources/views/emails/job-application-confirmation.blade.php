@extends('emails.layout')

@section('content')
    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 0;">
        💼 Application Received: {{ $jobTitle }}
    </h2>
    <p style="color: #475569; font-size: 14px;">
        Dear <strong>{{ $applicantName }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        Thank you for your interest in joining <strong>{{ $tenantName }}</strong>! We have successfully received your application for the <strong>{{ $jobTitle }}</strong> position.
    </p>

    <div class="card">
        <h4 style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 800;">
            Application Details
        </h4>
        <p style="margin: 4px 0; font-size: 13px;"><strong>Position:</strong> {{ $jobTitle }}</p>
        <p style="margin: 4px 0; font-size: 13px;"><strong>Applicant Email:</strong> {{ $applicantEmail }}</p>
        <p style="margin: 4px 0; font-size: 13px;"><strong>Submitted On:</strong> {{ now()->format('M d, Y') }}</p>
    </div>

    <p style="color: #475569; font-size: 14px;">
        Our recruiting team will carefully review your qualifications and portfolio. If your background aligns with our requirements, a hiring manager will reach out regarding next interview steps.
    </p>
@endsection
