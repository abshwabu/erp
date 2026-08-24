<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Notifications\Models\InAppNotification;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles notifications lifecycle: creating, listing, unread filtering, marking as read, and deleting', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Notifications Test Tenant',
        'slug'   => 'notiftest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Admin User',
            'email'     => 'admin@notif.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $staff = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Staff User',
            'email'     => 'staff@notif.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $staff->assignRole('Read Only');

        return [
            'admin' => $admin,
            'staff' => $staff,
        ];
    });

    $loginResponse = $this->postJson('http://notiftest.localhost/api/auth/login', [
        'email'    => 'admin@notif.test',
        'password' => 'password123',
    ]);
    $adminToken = $loginResponse->json('access_token');
    $adminHeaders = ['Authorization' => "Bearer {$adminToken}"];

    // 1. Admin sends a notification to staff
    $createResponse = $this->withHeaders($adminHeaders)->postJson('http://notiftest.localhost/api/notifications', [
        'user_id'    => $data['staff']->id,
        'type'       => 'warning',
        'title'      => 'Low Stock Alert',
        'message'    => 'SKU-001 is below the minimum reorder threshold.',
        'action_url' => '/inventory/low-stock',
    ]);
    $createResponse->assertStatus(201);
    $notifId1 = $createResponse->json('data.id');
    expect($notifId1)->not->toBeNull();
    expect($createResponse->json('data.type'))->toBe('warning');

    // Admin creates another notification for staff
    $createResponse2 = $this->withHeaders($adminHeaders)->postJson('http://notiftest.localhost/api/notifications', [
        'user_id' => $data['staff']->id,
        'type'    => 'info',
        'title'   => 'System Maintenance',
        'message' => 'Scheduled maintenance tonight at midnight.',
    ]);
    $createResponse2->assertStatus(201);
    $notifId2 = $createResponse2->json('data.id');

    // 2. Staff logs in and retrieves notifications
    $staffLoginResponse = $this->postJson('http://notiftest.localhost/api/auth/login', [
        'email'    => 'staff@notif.test',
        'password' => 'password123',
    ]);
    $staffToken = $staffLoginResponse->json('access_token');
    $staffHeaders = ['Authorization' => "Bearer {$staffToken}"];

    $listResponse = $this->withHeaders($staffHeaders)->getJson('http://notiftest.localhost/api/notifications');
    $listResponse->assertStatus(200);
    expect($listResponse->json('data'))->toHaveCount(2);
    expect($listResponse->json('unread_count'))->toBe(2);

    // 3. Staff marks one notification as read
    $markResponse = $this->withHeaders($staffHeaders)->postJson("http://notiftest.localhost/api/notifications/{$notifId1}/read");
    $markResponse->assertStatus(200);
    expect($markResponse->json('data.read_at'))->not->toBeNull();

    // 4. Verify unread count is now 1
    $listResponse2 = $this->withHeaders($staffHeaders)->getJson('http://notiftest.localhost/api/notifications?unread_only=1');
    $listResponse2->assertStatus(200);
    expect($listResponse2->json('data'))->toHaveCount(1);
    expect($listResponse2->json('unread_count'))->toBe(1);

    // 5. Staff marks all as read
    $markAllResponse = $this->withHeaders($staffHeaders)->postJson('http://notiftest.localhost/api/notifications/mark-all-read');
    $markAllResponse->assertStatus(200);

    $listResponse3 = $this->withHeaders($staffHeaders)->getJson('http://notiftest.localhost/api/notifications');
    $listResponse3->assertStatus(200);
    expect($listResponse3->json('unread_count'))->toBe(0);

    // 6. Delete notification
    $deleteResponse = $this->withHeaders($staffHeaders)->deleteJson("http://notiftest.localhost/api/notifications/{$notifId2}");
    $deleteResponse->assertStatus(204);

    $listResponse4 = $this->withHeaders($staffHeaders)->getJson('http://notiftest.localhost/api/notifications');
    $listResponse4->assertStatus(200);
    expect($listResponse4->json('data'))->toHaveCount(1);
});
