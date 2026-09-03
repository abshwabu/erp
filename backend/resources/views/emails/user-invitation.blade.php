@extends('emails.layout')

@section('content')
    <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 0;">
        👋 You've Been Invited to Join {{ $tenantName }}
    </h2>
    <p style="color: #475569; font-size: 14px;">
        Hello <strong>{{ $user->name }}</strong>,
    </p>
    <p style="color: #475569; font-size: 14px;">
        An administrator has provisioned an account for you on the <strong>{{ $tenantName }}</strong> workspace on the ERP System.
    </p>

    <div class="card">
        <h4 style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 800;">
            Account Credentials
        </h4>
        <p style="margin: 4px 0; font-size: 13px;"><strong>Email:</strong> {{ $user->email }}</p>
        @if(!empty($temporaryPassword))
            <p style="margin: 4px 0; font-size: 13px;"><strong>Temporary Password:</strong> <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">{{ $temporaryPassword }}</code></p>
        @endif
        @if(!empty($roleName))
            <p style="margin: 4px 0; font-size: 13px;"><strong>Assigned Role:</strong> <span class="badge badge-indigo">{{ $roleName }}</span></p>
        @endif
    </div>

    <div style="text-align: center; margin: 28px 0;">
        <a href="{{ $loginUrl ?? url('/login') }}" class="button">
            Sign In to Workspace
        </a>
    </div>

    <p style="color: #64748b; font-size: 12px; margin-bottom: 0;">
        For security, we recommend changing your password after your initial sign-in under Account Settings.
    </p>
@endsection
