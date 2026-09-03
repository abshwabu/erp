@extends('emails.layout')

@section('content')
    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 0;">
        🔐 Password Reset Request
    </h2>
    <p style="color: #475569; font-size: 14px;">
        Hello <strong>{{ $user->name }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        We received a request to reset the password for your account (<strong>{{ $user->email }}</strong>). You can set a new password by clicking the button below:
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $resetUrl }}" class="button">
            Reset Account Password
        </a>
    </div>

    <div class="card">
        <p style="margin: 0; font-size: 12px; color: #64748b;">
            ⚠️ This secure reset link is valid for <strong>60 minutes</strong>. If you did not request a password reset, you can safely ignore this email; your password will remain unchanged.
        </p>
    </div>

    <p style="color: #94a3b8; font-size: 11px; word-break: break-all; margin-top: 24px;">
        If the button above does not work, copy and paste this link into your browser:<br>
        <a href="{{ $resetUrl }}" style="color: #4f46e5;">{{ $resetUrl }}</a>
    </p>
@endsection
