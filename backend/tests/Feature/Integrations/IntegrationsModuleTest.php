<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles integrations lifecycle: connector setup, testing, log recording, updates, and deletion', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Integrations Test Tenant',
        'slug'   => 'inttest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'DevOps Admin',
            'email'     => 'admin@int.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        return ['admin' => $admin];
    });

    $loginResponse = $this->postJson('http://inttest.localhost/api/auth/login', [
        'email'    => 'admin@int.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create an Integration
    $createResponse = $this->withHeaders($headers)->postJson('http://inttest.localhost/api/integrations', [
        'provider'    => 'stripe',
        'name'        => 'Stripe Payments Production',
        'api_key'     => 'sk_live_1234567890abcdef',
        'webhook_url' => 'https://api.inttest.com/webhooks/stripe',
        'settings'    => ['auto_capture' => true, 'currency' => 'USD'],
    ]);
    $createResponse->assertStatus(201);
    $intId = $createResponse->json('data.id');
    expect($intId)->not->toBeNull();
    expect($createResponse->json('data.provider'))->toBe('stripe');
    expect($createResponse->json('data.api_key'))->toBeNull(); // hidden

    // 2. Test Connection
    $testResponse = $this->withHeaders($headers)->postJson("http://inttest.localhost/api/integrations/{$intId}/test");
    $testResponse->assertStatus(200);
    expect($testResponse->json('data.status'))->toBe('connected');
    expect($testResponse->json('data.last_tested_at'))->not->toBeNull();

    // 3. List integrations
    $listResponse = $this->withHeaders($headers)->getJson('http://inttest.localhost/api/integrations');
    $listResponse->assertStatus(200);
    expect($listResponse->json('data'))->toHaveCount(1);
    expect($listResponse->json('data.0.logs_count'))->toBe(1);

    // 4. Retrieve integration details with logs
    $detailResponse = $this->withHeaders($headers)->getJson("http://inttest.localhost/api/integrations/{$intId}");
    $detailResponse->assertStatus(200);
    expect($detailResponse->json('data.logs'))->toHaveCount(1);
    expect($detailResponse->json('data.logs.0.event'))->toBe('health_check');

    // 5. Update settings
    $updateResponse = $this->withHeaders($headers)->putJson("http://inttest.localhost/api/integrations/{$intId}", [
        'name' => 'Stripe Payments Live Primary',
    ]);
    $updateResponse->assertStatus(200);
    expect($updateResponse->json('data.name'))->toBe('Stripe Payments Live Primary');

    // 6. Delete integration
    $deleteResponse = $this->withHeaders($headers)->deleteJson("http://inttest.localhost/api/integrations/{$intId}");
    $deleteResponse->assertStatus(204);

    $listAfter = $this->withHeaders($headers)->getJson('http://inttest.localhost/api/integrations');
    $listAfter->assertStatus(200);
    expect($listAfter->json('data'))->toHaveCount(0);
});
