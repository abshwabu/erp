<?php

declare(strict_types=1);

namespace Tests;

use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Tenant;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'id' => (string) Str::uuid(),
        ], $attributes));
    }

    protected function runInTenant(Tenant $tenant, Closure $callback): mixed
    {
        tenancy()->initialize($tenant);

        try {
            return $callback($tenant);
        } finally {
            tenancy()->end();
        }
    }

    protected function initializeTenant(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);
    }

    protected function endTenant(): void
    {
        tenancy()->end();
    }

    protected function tearDown(): void
    {
        tenancy()->end();

        parent::tearDown();
    }
}
