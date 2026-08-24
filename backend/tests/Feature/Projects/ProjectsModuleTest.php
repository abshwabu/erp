<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles projects lifecycle: project creation, task management, hours logging, and status transitions', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Projects Test Tenant',
        'slug'   => 'prjtest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Project Admin',
            'email'     => 'admin@prj.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $dev = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Alex Engineer',
            'email'     => 'alex@prj.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $dev->assignRole('Read Only');

        return [
            'admin' => $admin,
            'dev'   => $dev,
        ];
    });

    $loginResponse = $this->postJson('http://prjtest.localhost/api/auth/login', [
        'email'    => 'admin@prj.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a Project
    $createResponse = $this->withHeaders($headers)->postJson('http://prjtest.localhost/api/projects', [
        'name'         => 'Mobile App Redesign',
        'description'  => 'Overhaul mobile user experience for version 2.0',
        'manager_id'   => $data['admin']->id,
        'status'       => 'planned',
        'budget_cents' => 5000000, // $50,000.00
        'start_date'   => '2026-09-01',
        'due_date'     => '2026-12-31',
    ]);
    $createResponse->assertStatus(201);
    $projectId = $createResponse->json('data.id');
    expect($projectId)->not->toBeNull();
    expect($createResponse->json('data.code'))->toBe('PRJ-0001');
    expect($createResponse->json('data.status'))->toBe('planned');

    // 2. Update Project status to in_progress
    $updateResponse = $this->withHeaders($headers)->putJson("http://prjtest.localhost/api/projects/{$projectId}", [
        'status' => 'in_progress',
    ]);
    $updateResponse->assertStatus(200);
    expect($updateResponse->json('data.status'))->toBe('in_progress');

    // 3. Add a Task to the Project
    $taskResponse = $this->withHeaders($headers)->postJson("http://prjtest.localhost/api/projects/{$projectId}/tasks", [
        'title'           => 'Wireframing & Prototypes',
        'description'     => 'Create Figma high-fidelity wireframes',
        'assigned_to'     => $data['dev']->id,
        'status'          => 'todo',
        'priority'        => 'high',
        'due_date'        => '2026-09-15',
        'estimated_hours' => 40,
    ]);
    $taskResponse->assertStatus(201);
    $taskId = $taskResponse->json('data.id');
    expect($taskId)->not->toBeNull();
    expect($taskResponse->json('data.estimated_hours'))->toBe(40);
    expect($taskResponse->json('data.logged_hours'))->toBe(0);

    // 4. Update task status and log hours
    $updateTaskResponse = $this->withHeaders($headers)->putJson("http://prjtest.localhost/api/projects/{$projectId}/tasks/{$taskId}", [
        'status'       => 'done',
        'logged_hours' => 38,
    ]);
    $updateTaskResponse->assertStatus(200);
    expect($updateTaskResponse->json('data.status'))->toBe('done');
    expect($updateTaskResponse->json('data.logged_hours'))->toBe(38);

    // 5. Retrieve project details with tasks and manager
    $detailResponse = $this->withHeaders($headers)->getJson("http://prjtest.localhost/api/projects/{$projectId}");
    $detailResponse->assertStatus(200);
    expect($detailResponse->json('data.tasks'))->toHaveCount(1);
    expect($detailResponse->json('data.manager.id'))->toBe($data['admin']->id);

    // 6. List projects
    $listResponse = $this->withHeaders($headers)->getJson('http://prjtest.localhost/api/projects');
    $listResponse->assertStatus(200);
    expect($listResponse->json('data.data'))->toHaveCount(1);
    expect($listResponse->json('data.data.0.tasks_count'))->toBe(1);
});
