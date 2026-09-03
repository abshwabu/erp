@extends('emails.layout')

@section('content')
    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 0;">
        🛡️ Security Verification Code
    </h2>
    <p style="color: #475569; font-size: 14px;">
        Hello <strong>{{ $user->name }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        Use the following 6-digit verification code to complete your two-factor authentication sign-in:
    </p>

    <div style="text-align: center; margin: 28px 0;">
        <div style="display: inline-block; background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 16px 36px;">
            <span style="font-family: 'Courier New', Courier, monospace; font-size: 32px; font-weight: 900; letter-spacing: 8px; color: #1e1b4b;">
                {{ $code }}
            </span>
        </div>
    </div>

    <div class="card">
        <p style="margin: 0; font-size: 12px; color: #64748b;">
            ⏱️ This code will expire in <strong>10 minutes</strong>. Never share this code with anyone. Our support team will never ask for your verification code.
        </p>
    </div>
@endsection
