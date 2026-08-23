<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles tenant display preferences and settings configuration', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Acme Global Enterprises',
        'slug'   => 'settingstest',
        'status' => 'active',
    ]);

    $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Settings Admin',
            'email'     => 'admin@settings.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');
    });

    $loginResponse = $this->postJson('http://settingstest.localhost/api/auth/login', [
        'email'    => 'admin@settings.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Get default settings
    $getResponse = $this->withHeaders($headers)->getJson('http://settingstest.localhost/api/core/settings');
    $getResponse->assertStatus(200);
    expect($getResponse->json('data.display_name'))->toBe('Acme Global Enterprises');
    expect($getResponse->json('data.timezone'))->toBe('UTC');
    expect($getResponse->json('data.currency'))->toBe('USD');

    // 2. Update settings
    $updateResponse = $this->withHeaders($headers)->postJson('http://settingstest.localhost/api/core/settings', [
        'display_name' => 'Acme HQ EMEA',
        'timezone'     => 'Europe/London',
        'currency'     => 'GBP',
    ]);
    $updateResponse->assertStatus(200);
    expect($updateResponse->json('data.display_name'))->toBe('Acme HQ EMEA');
    expect($updateResponse->json('data.timezone'))->toBe('Europe/London');
    expect($updateResponse->json('data.currency'))->toBe('GBP');

    // 3. Verify settings persist across subsequent GET requests
    $verifyResponse = $this->withHeaders($headers)->getJson('http://settingstest.localhost/api/core/settings');
    $verifyResponse->assertStatus(200);
    expect($verifyResponse->json('data.display_name'))->toBe('Acme HQ EMEA');
    expect($verifyResponse->json('data.timezone'))->toBe('Europe/London');
    expect($verifyResponse->json('data.currency'))->toBe('GBP');
});
