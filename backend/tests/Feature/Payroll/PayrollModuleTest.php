<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\HR\Models\Department;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\Position;
use App\Modules\Payroll\Models\PayrollRun;
use App\Modules\Payroll\Models\Payslip;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles payroll workflows: creating runs, processing active employees, and payslip generation', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Payroll Test Tenant',
        'slug'   => 'payrolltest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Payroll Admin',
            'email'     => 'payroll@test.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Owner');

        $dept = Department::create([
            'name' => 'Finance',
            'cost_center' => 'FIN-01',
        ]);

        $position = Position::create([
            'department_id'    => $dept->id,
            'title'            => 'Senior Financial Analyst',
            'job_grade'        => 'L5',
            'min_salary_cents' => 600000,
            'max_salary_cents' => 800000,
            'is_active'        => true,
        ]);

        // Employee with position: should resolve to average (min + max)/2 = 700,000 cents
        $emp1 = Employee::create([
            'employee_number' => 'EMP-101',
            'first_name'      => 'Claire',
            'last_name'       => 'Redfield',
            'email'           => 'claire@resident.test',
            'employment_type' => 'full_time',
            'department_id'   => $dept->id,
            'position_id'     => $position->id,
            'start_date'      => '2025-01-01',
            'status'          => 'active',
        ]);

        // Employee without position: should resolve to DEFAULT_GROSS_CENTS = 100,000 cents
        $emp2 = Employee::create([
            'employee_number' => 'EMP-102',
            'first_name'      => 'Leon',
            'last_name'       => 'Kennedy',
            'email'           => 'leon@resident.test',
            'employment_type' => 'full_time',
            'department_id'   => $dept->id,
            'position_id'     => null,
            'start_date'      => '2025-02-01',
            'status'          => 'active',
        ]);

        // Inactive / Terminated employee: should NOT receive a payslip
        $emp3 = Employee::create([
            'employee_number' => 'EMP-103',
            'first_name'      => 'Albert',
            'last_name'       => 'Wesker',
            'email'           => 'wesker@resident.test',
            'employment_type' => 'full_time',
            'department_id'   => $dept->id,
            'position_id'     => $position->id,
            'start_date'      => '2024-01-01',
            'status'          => 'terminated',
        ]);

        return [
            'admin' => $admin,
            'emp1'  => $emp1,
            'emp2'  => $emp2,
            'emp3'  => $emp3,
        ];
    });

    $loginResponse = $this->postJson('http://payrolltest.localhost/api/auth/login', [
        'email'    => 'payroll@test.com',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a payroll run in draft
    $createRunResponse = $this->withHeaders($headers)->postJson('http://payrolltest.localhost/api/payroll/runs', [
        'period_start' => '2026-08-01',
        'period_end'   => '2026-08-31',
    ]);
    $createRunResponse->assertStatus(201);
    $runId = $createRunResponse->json('data.id');
    expect($runId)->not->toBeNull();
    expect($createRunResponse->json('data.status'))->toBe('draft');
    expect($createRunResponse->json('data.period_start'))->toStartWith('2026-08-01');
    expect($createRunResponse->json('data.period_end'))->toStartWith('2026-08-31');

    // 2. List payroll runs
    $listRunsResponse = $this->withHeaders($headers)->getJson('http://payrolltest.localhost/api/payroll/runs');
    $listRunsResponse->assertStatus(200);
    expect($listRunsResponse->json('data'))->toHaveCount(1);
    expect($listRunsResponse->json('data.0.payslips_count'))->toBe(0);

    // 3. Process the payroll run
    $processResponse = $this->withHeaders($headers)->postJson("http://payrolltest.localhost/api/payroll/runs/{$runId}/process");
    $processResponse->assertStatus(200);
    expect($processResponse->json('data.status'))->toBe('processed');
    expect($processResponse->json('data.processed_at'))->not->toBeNull();
    expect($processResponse->json('data.payslips'))->toHaveCount(2);

    // 4. Fetch payslips for the run
    $payslipsResponse = $this->withHeaders($headers)->getJson("http://payrolltest.localhost/api/payroll/runs/{$runId}/payslips");
    $payslipsResponse->assertStatus(200);
    $payslips = $payslipsResponse->json('data');
    expect($payslips)->toHaveCount(2);

    $claireSlip = collect($payslips)->firstWhere('employee_id', $data['emp1']->id);
    expect($claireSlip)->not->toBeNull();
    expect($claireSlip['gross_cents'])->toBe(700000);
    expect($claireSlip['deductions_cents'])->toBe(0);
    expect($claireSlip['net_cents'])->toBe(700000);

    $leonSlip = collect($payslips)->firstWhere('employee_id', $data['emp2']->id);
    expect($leonSlip)->not->toBeNull();
    expect($leonSlip['gross_cents'])->toBe(100000);
    expect($leonSlip['deductions_cents'])->toBe(0);
    expect($leonSlip['net_cents'])->toBe(100000);

    // 5. Attempt to re-process already processed run (must be rejected with 422)
    $reprocessResponse = $this->withHeaders($headers)->postJson("http://payrolltest.localhost/api/payroll/runs/{$runId}/process");
    $reprocessResponse->assertStatus(422);
});
