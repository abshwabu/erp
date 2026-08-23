<?php

declare(strict_types=1);

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

it('creates, reads, updates, and deletes an account', function (): void {
    $tenant = Tenant::create([
        'name'   => 'COA Test Tenant',
        'slug'   => 'coatest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();
        (new AccountingSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Admin User',
            'email'     => 'admin@coa.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $type = AccountType::where('name', 'Asset')->firstOrFail();

        return ['admin' => $admin, 'type' => $type];
    });

    $loginResponse = $this->postJson('http://coatest.localhost/api/auth/login', [
        'email'    => 'admin@coa.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create account
    $createResponse = $this->withHeaders($headers)->postJson('http://coatest.localhost/api/accounting/accounts', [
        'code'            => '1050',
        'name'            => 'Petty Cash USD',
        'account_type_id' => $data['type']->id,
        'currency_code'   => 'USD',
        'description'     => 'Office petty cash',
    ]);

    $createResponse->assertStatus(201);
    $accountId = $createResponse->json('id');
    expect($accountId)->not->toBeNull();

    // 2. Fetch account
    $showResponse = $this->withHeaders($headers)->getJson("http://coatest.localhost/api/accounting/accounts/{$accountId}");
    $showResponse->assertStatus(200);
    $showResponse->assertJsonPath('name', 'Petty Cash USD');

    // 3. Update account
    $updateResponse = $this->withHeaders($headers)->patchJson("http://coatest.localhost/api/accounting/accounts/{$accountId}", [
        'name' => 'Petty Cash Main Office',
    ]);
    $updateResponse->assertStatus(200);
    $updateResponse->assertJsonPath('name', 'Petty Cash Main Office');

    // 4. Delete account
    $deleteResponse = $this->withHeaders($headers)->deleteJson("http://coatest.localhost/api/accounting/accounts/{$accountId}");
    $deleteResponse->assertStatus(204);
});

it('imports accounts from CSV successfully', function (): void {
    $tenant = Tenant::create([
        'name'   => 'COA Import Tenant',
        'slug'   => 'coaimport',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();
        (new AccountingSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Admin User',
            'email'     => 'admin@import.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        return ['admin' => $admin];
    });

    $loginResponse = $this->postJson('http://coaimport.localhost/api/auth/login', [
        'email'    => 'admin@import.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    $csvContent = "code,name,account_type,description,currency_code\n" .
        "1090,Security Deposit,Asset,Rental deposits,USD\n" .
        "5050,Software Subscriptions,Expense,SaaS tooling,USD\n";

    $file = UploadedFile::fake()->createWithContent('coa.csv', $csvContent);

    $importResponse = $this->withHeaders($headers)->postJson('http://coaimport.localhost/api/accounting/accounts/import', [
        'file' => $file,
    ]);

    $importResponse->assertStatus(200);
    $importResponse->assertJsonPath('imported_count', 2);

    $tenant->run(function () {
        expect(Account::where('code', '1090')->exists())->toBeTrue();
        expect(Account::where('code', '5050')->exists())->toBeTrue();
    });
});
