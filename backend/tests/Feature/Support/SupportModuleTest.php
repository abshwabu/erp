<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles support desk lifecycle: ticket creation, agent assignment, replies, resolution, and deletion', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Support Test Tenant',
        'slug'   => 'supptest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Support Lead',
            'email'     => 'lead@supp.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $agent = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Tier 1 Agent',
            'email'     => 'agent@supp.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $agent->assignRole('Admin');

        return [
            'admin' => $admin,
            'agent' => $agent,
        ];
    });

    $loginResponse = $this->postJson('http://supptest.localhost/api/auth/login', [
        'email'    => 'lead@supp.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a support ticket
    $createResponse = $this->withHeaders($headers)->postJson('http://supptest.localhost/api/support/tickets', [
        'subject'       => 'Cannot connect to POS barcode scanner',
        'message'       => 'The USB scanner is not recognized on terminal 2 after reboot.',
        'contact_name'  => 'Store Manager Downtown',
        'contact_email' => 'store1@retail.test',
        'priority'      => 'high',
        'channel'       => 'web',
    ]);
    $createResponse->assertStatus(201);
    $ticketId = $createResponse->json('data.id');
    expect($ticketId)->not->toBeNull();
    expect($createResponse->json('data.ticket_number'))->toBe('TCK-00001');
    expect($createResponse->json('data.status'))->toBe('open');
    expect($createResponse->json('data.priority'))->toBe('high');
    expect($createResponse->json('data.messages'))->toHaveCount(1);

    // 2. Assign ticket to agent
    $assignResponse = $this->withHeaders($headers)->putJson("http://supptest.localhost/api/support/tickets/{$ticketId}", [
        'assigned_to' => $data['agent']->id,
        'status'      => 'in_progress',
    ]);
    $assignResponse->assertStatus(200);
    expect($assignResponse->json('data.status'))->toBe('in_progress');
    expect($assignResponse->json('data.assigned_to'))->toBe($data['agent']->id);

    // 3. Agent replies to ticket
    $replyResponse = $this->withHeaders($headers)->postJson("http://supptest.localhost/api/support/tickets/{$ticketId}/reply", [
        'message'     => 'Please unplug the USB cable and reconnect while holding the reset pin.',
        'is_internal' => false,
    ]);
    $replyResponse->assertStatus(201);
    expect($replyResponse->json('data.sender_type'))->toBe('agent');

    // 4. Add internal note
    $noteResponse = $this->withHeaders($headers)->postJson("http://supptest.localhost/api/support/tickets/{$ticketId}/reply", [
        'message'     => 'Known firmware glitch with model XT-200. If reset fails, schedule RMA.',
        'is_internal' => true,
    ]);
    $noteResponse->assertStatus(201);
    expect($noteResponse->json('data.is_internal'))->toBeTrue();

    // 5. Retrieve ticket conversation thread
    $detailResponse = $this->withHeaders($headers)->getJson("http://supptest.localhost/api/support/tickets/{$ticketId}");
    $detailResponse->assertStatus(200);
    expect($detailResponse->json('data.messages'))->toHaveCount(3);

    // 6. Resolve ticket
    $resolveResponse = $this->withHeaders($headers)->putJson("http://supptest.localhost/api/support/tickets/{$ticketId}", [
        'status' => 'resolved',
    ]);
    $resolveResponse->assertStatus(200);
    expect($resolveResponse->json('data.status'))->toBe('resolved');
    expect($resolveResponse->json('data.resolved_at'))->not->toBeNull();

    // 7. List tickets
    $listResponse = $this->withHeaders($headers)->getJson('http://supptest.localhost/api/support/tickets');
    $listResponse->assertStatus(200);
    expect($listResponse->json('data.data'))->toHaveCount(1);

    // 8. Delete ticket
    $deleteResponse = $this->withHeaders($headers)->deleteJson("http://supptest.localhost/api/support/tickets/{$ticketId}");
    $deleteResponse->assertStatus(204);

    $listAfterDelete = $this->withHeaders($headers)->getJson('http://supptest.localhost/api/support/tickets');
    $listAfterDelete->assertStatus(200);
    expect($listAfterDelete->json('data.data'))->toHaveCount(0);
});
