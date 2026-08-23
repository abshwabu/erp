<?php

declare(strict_types=1);

use App\Modules\Core\Enums\Permission;
use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

it('handles forgot password and reset password flow successfully', function (): void {
    Notification::fake();

    $tenant = Tenant::create([
        'name'   => 'Auth Flow Tenant',
        'slug'   => 'authflow',
        'status' => 'active',
    ]);

    $user = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        return User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Reset User',
            'email'     => 'reset@test.com',
            'password'  => Hash::make('oldpassword123'),
            'is_active' => true,
        ]);
    });

    // Request reset password link
    $response = $this->postJson('http://authflow.localhost/api/auth/forgot-password', [
        'email' => 'reset@test.com',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'If the email exists, a password reset link has been sent.',
    ]);

    // Verify token in DB
    $tokenRecord = $tenant->run(function () {
        return DB::table('password_reset_tokens')->where('email', 'reset@test.com')->first();
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

    $resetResponse = $this->postJson('http://authflow.localhost/api/auth/reset-password', [
        'email'                 => 'reset@test.com',
        'token'                 => $rawToken,
        'password'              => 'newsecurepassword123',
        'password_confirmation' => 'newsecurepassword123',
    ]);

    $resetResponse->assertStatus(200);
    $resetResponse->assertJson([
        'message' => 'Password reset successfully.',
    ]);

    // Verify login works with new password
    $loginResponse = $this->postJson('http://authflow.localhost/api/auth/login', [
        'email'    => 'reset@test.com',
        'password' => 'newsecurepassword123',
    ]);

    $loginResponse->assertStatus(200);
    $loginResponse->assertJsonStructure(['access_token', 'refresh_token']);
});

it('lists permissions and allows role creation with custom permissions', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Role Test Tenant',
        'slug'   => 'roletest',
        'status' => 'active',
    ]);

    $admin = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Admin User',
            'email'     => 'admin@test.com',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');
        return $admin;
    });

    $loginResponse = $this->postJson('http://roletest.localhost/api/auth/login', [
        'email'    => 'admin@test.com',
        'password' => 'password',
    ]);
    $token = $loginResponse->json('access_token');

    // List permissions
    $permResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('http://roletest.localhost/api/permissions');

    $permResponse->assertStatus(200);
    $permResponse->assertJsonStructure(['data']);

    // Create new role
    $roleResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('http://roletest.localhost/api/roles', [
            'name' => 'Custom Inventory Manager',
        ]);

    $roleResponse->assertStatus(201);
    $roleId = $roleResponse->json('data.id');

    // Sync permissions
    $syncResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("http://roletest.localhost/api/roles/{$roleId}/permissions", [
            'permissions' => [
                Permission::InventoryProductsView->value,
                Permission::InventoryStockView->value,
                Permission::InventoryStockAdjust->value,
            ],
        ]);

    $syncResponse->assertStatus(200);
    $syncResponse->assertJsonFragment([
        Permission::InventoryStockAdjust->value,
    ]);
});
