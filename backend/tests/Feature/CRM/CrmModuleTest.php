<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles CRM contacts management: creating leads and customers and listing contacts', function (): void {
    $tenant = Tenant::create([
        'name'   => 'CRM Test Tenant',
        'slug'   => 'crmtest',
        'status' => 'active',
    ]);

    $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'CRM Admin',
            'email'     => 'crm@test.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');
    });

    $loginResponse = $this->postJson('http://crmtest.localhost/api/auth/login', [
        'email'    => 'crm@test.com',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a lead contact
    $leadResponse = $this->withHeaders($headers)->postJson('http://crmtest.localhost/api/crm/contacts', [
        'name'    => 'Sarah Connor',
        'company' => 'Cyberdyne Resistance',
        'email'   => 'sarah@resistance.test',
        'phone'   => '+1-555-4321',
        'status'  => 'lead',
    ]);
    $leadResponse->assertStatus(201);
    expect($leadResponse->json('data.name'))->toBe('Sarah Connor');
    expect($leadResponse->json('data.company'))->toBe('Cyberdyne Resistance');

    // 2. Create a customer contact
    $customerResponse = $this->withHeaders($headers)->postJson('http://crmtest.localhost/api/crm/contacts', [
        'name'    => 'John Connor',
        'company' => 'TechCom Inc',
        'email'   => 'john@techcom.test',
        'phone'   => '+1-555-8765',
        'status'  => 'customer',
    ]);
    $customerResponse->assertStatus(201);
    expect($customerResponse->json('data.name'))->toBe('John Connor');

    // 3. List contacts
    $listResponse = $this->withHeaders($headers)->getJson('http://crmtest.localhost/api/crm/contacts');
    $listResponse->assertStatus(200);
    $contacts = $listResponse->json('data');
    expect($contacts)->toHaveCount(2);
});
