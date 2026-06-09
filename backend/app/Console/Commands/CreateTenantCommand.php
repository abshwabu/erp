<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Core\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateTenantCommand extends Command
{
    protected $signature = 'erp:tenant:create {name} {email} {--domain=} {--plan-id=} {--status=active}';

    protected $description = 'Create a tenant, provision its schema, and seed default data.';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $email = (string) $this->argument('email');
        $slug = Str::slug($name);

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'custom_domain' => $this->option('domain'),
            'plan_id' => $this->option('plan-id'),
            'status' => $this->option('status'),
            'settings' => [
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'currency' => config('app.currency', 'USD'),
                'owner_email' => $email,
            ],
        ]);

        $this->info("Created tenant {$tenant->name} ({$tenant->slug}).");

        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->getTenantKey()],
        ]);

        $this->seedDefaultData($tenant, $email);

        $this->line(Artisan::output());

        return self::SUCCESS;
    }

    private function seedDefaultData(Tenant $tenant, string $email): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $tenant->name . ' Owner',
                'password' => Str::random(32),
            ]
        );

        DB::connection(config('tenancy.database.central_connection'))
            ->table('tenant_user')
            ->updateOrInsert(
                [
                    'tenant_id' => $tenant->getTenantKey(),
                    'user_id' => $user->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
    }
}
