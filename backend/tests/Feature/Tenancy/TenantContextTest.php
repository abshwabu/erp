<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;

it('resolves the tenant from the subdomain and boots tenancy', function (): void {
    $tenant = Tenant::create([
        'name' => 'Acme ERP',
        'slug' => 'acme',
        'status' => 'active',
        'settings' => [
            'timezone' => 'Africa/Nairobi',
            'locale' => 'en',
            'currency' => 'KES',
        ],
    ]);

    $response = $this
        ->getJson('http://acme.localhost/api/tenant-context');

    $response
        ->assertOk()
        ->assertJsonPath('tenant_id', $tenant->getKey())
        ->assertJsonPath('tenant_slug', $tenant->slug)
        ->assertJsonPath('timezone', 'Africa/Nairobi')
        ->assertJsonPath('locale', 'en')
        ->assertJsonPath('currency', 'KES');
});
