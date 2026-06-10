<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Modules\Core\Services\MfaService;

it('authenticates a user and returns JWT tokens', function (): void {
    $tenant = Tenant::create([
        'name' => 'Acme Test',
        'slug' => 'acme',
        'status' => 'active',
    ]);
    
    $tenant->run(function () use ($tenant) {
        User::create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    });

    $response = $this->postJson('http://acme.localhost/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
        ]);
});

it('returns 401 for invalid credentials', function (): void {
    $tenant = Tenant::create([
        'name' => 'Acme Test',
        'slug' => 'acme',
        'status' => 'active',
    ]);

    $response = $this->postJson('http://acme.localhost/api/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

it('returns 401 for an expired access token', function (): void {
    $tenant = Tenant::create([
        'name' => 'Acme Test',
        'slug' => 'acme',
        'status' => 'active',
    ]);
    
    $user = $tenant->run(function () use ($tenant) {
        return User::create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    });

    // Manually construct a token that expired 60 seconds ago
    $claims = [
        'sub' => $user->id,
        'iat' => time() - 120,
        'exp' => time() - 60,
        'nbf' => time() - 120,
        'jti' => Illuminate\Support\Str::random(16),
        'type' => 'access',
    ];

    $token = auth('api')->manager()->getJWTProvider()->encode($claims);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('http://acme.localhost/api/auth/me');

    $response->assertStatus(401);
});

it('invalidates a token upon logout', function (): void {
    $tenant = Tenant::create([
        'name' => 'Acme Test',
        'slug' => 'acme',
        'status' => 'active',
    ]);
    
    $user = $tenant->run(function () use ($tenant) {
        return User::create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    });

    $loginResponse = $this->postJson('http://acme.localhost/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $token = $loginResponse->json('access_token');

    // Logout
    $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('http://acme.localhost/api/auth/logout');

    $logoutResponse->assertOk();

    // Verify me returns 401 now
    $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('http://acme.localhost/api/auth/me');

    $meResponse->assertStatus(401);
});

it('handles the password reset flow end-to-end', function (): void {
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Acme Test',
        'slug' => 'acme',
        'status' => 'active',
    ]);
    
    $user = $tenant->run(function () use ($tenant) {
        return User::create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    });

    // Request reset link
    $forgotResponse = $this->postJson('http://acme.localhost/api/auth/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $forgotResponse->assertOk();

    // Retrieve token from database
    $tokenRecord = $tenant->run(function () {
        return DB::table('password_reset_tokens')->first();
    });

    expect($tokenRecord)->not->toBeNull();

    $rawToken = null;

    Notification::assertSentTo(
        $user,
        \Illuminate\Auth\Notifications\ResetPassword::class,
        function ($notification) use (&$rawToken) {
            $rawToken = $notification->token;
            return true;
        }
    );

    expect($rawToken)->not->toBeNull();

    // Reset password
    $resetResponse = $this->postJson('http://acme.localhost/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $rawToken,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $resetResponse->assertOk();

    // Try logging in with new password
    $loginResponse = $this->postJson('http://acme.localhost/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'newpassword123',
    ]);

    $loginResponse->assertOk();
});

it('handles MFA enrollment and verification challenge', function (): void {
    $tenant = Tenant::create([
        'name' => 'Acme Test',
        'slug' => 'acme',
        'status' => 'active',
    ]);
    
    $user = $tenant->run(function () use ($tenant) {
        return User::create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    });

    // 1. Login to get access token
    $loginResponse = $this->postJson('http://acme.localhost/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);
    $token = $loginResponse->json('access_token');

    // 2. Enable MFA (returns secret and QR code URI)
    $enableResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('http://acme.localhost/api/auth/mfa/enable');

    $enableResponse->assertOk()
        ->assertJsonStructure(['secret', 'qr_code_uri']);
    
    $secret = $enableResponse->json('secret');

    // 3. Verify MFA with code
    $mfaService = app(MfaService::class);
    $timeSlice = (int) floor(time() / 30);
    
    $reflection = new \ReflectionClass($mfaService);
    $method = $reflection->getMethod('calculateCode');
    $method->setAccessible(true);
    
    $reflectionBase32 = $reflection->getMethod('base32Decode');
    $reflectionBase32->setAccessible(true);
    $key = $reflectionBase32->invoke($mfaService, $secret);
    $validCode = $method->invoke($mfaService, $key, $timeSlice);

    $verifyResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('http://acme.localhost/api/auth/mfa/verify', [
            'code' => $validCode,
        ]);

    $verifyResponse->assertOk();

    // 4. Try logging in again (should require MFA challenge)
    $challengeLoginResponse = $this->postJson('http://acme.localhost/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $challengeLoginResponse->assertOk()
        ->assertJsonStructure(['mfa_required', 'mfa_token']);

    $mfaToken = $challengeLoginResponse->json('mfa_token');

    // 5. Complete MFA challenge
    $challengeResponse = $this->postJson('http://acme.localhost/api/auth/mfa/challenge', [
        'mfa_token' => $mfaToken,
        'code' => $validCode,
    ]);

    $challengeResponse->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token']);
});
