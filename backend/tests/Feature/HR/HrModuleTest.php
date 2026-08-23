<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\HR\Models\AttendanceLog;
use App\Modules\HR\Models\Department;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\LeaveEntitlement;
use App\Modules\HR\Models\LeaveType;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('completes HR workflows: department, employee, leave request/approval and attendance filtering', function (): void {
    $tenant = Tenant::create([
        'name'   => 'HR Test Tenant',
        'slug'   => 'hrtest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $hrAdmin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'HR Admin',
            'email'     => 'hr@test.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $hrAdmin->assignRole('Admin');

        $dept = Department::create([
            'name' => 'Engineering',
            'cost_center' => 'ENG-01',
        ]);

        $manager = Employee::create([
            'employee_number' => 'EMP-001',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@company.test',
            'employment_type' => 'full_time',
            'department_id' => $dept->id,
            'start_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $dept->update(['manager_id' => $manager->id]);

        $employee = Employee::create([
            'employee_number' => 'EMP-002',
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@company.test',
            'employment_type' => 'full_time',
            'department_id' => $dept->id,
            'manager_id' => $manager->id,
            'start_date' => '2026-01-15',
            'status' => 'active',
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'code' => 'AL',
            'max_days_per_year' => 20,
            'is_paid' => true,
        ]);

        LeaveEntitlement::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => (int) date('Y'),
            'entitled_days' => 20,
            'accrued_days' => 10,
            'taken_days' => 0,
            'carried_over_days' => 0,
        ]);

        // Attendance records
        AttendanceLog::create([
            'employee_id' => $employee->id,
            'clock_type'  => 'in',
            'logged_at'   => date('Y-m-d') . ' 09:00:00',
            'method'      => 'web',
        ]);

        return [
            'hrAdmin' => $hrAdmin,
            'dept' => $dept,
            'manager' => $manager,
            'employee' => $employee,
            'leaveType' => $leaveType,
        ];
    });

    $loginResponse = $this->postJson('http://hrtest.localhost/api/auth/login', [
        'email'    => 'hr@test.com',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Fetch departments
    $deptResponse = $this->withHeaders($headers)->getJson('http://hrtest.localhost/api/hr/departments');
    $deptResponse->assertStatus(200);
    $deptResponse->assertJsonCount(1);

    // 2. Submit Leave Request
    $leaveResponse = $this->withHeaders($headers)->postJson('http://hrtest.localhost/api/hr/leave/requests', [
        'employee_id'   => $data['employee']->id,
        'leave_type_id' => $data['leaveType']->id,
        'start_date'    => date('Y') . '-09-01',
        'end_date'      => date('Y') . '-09-03',
        'reason'        => 'Vacation trip',
    ]);
    $leaveResponse->assertStatus(201);
    $leaveRequestId = $leaveResponse->json('id');
    expect($leaveRequestId)->not->toBeNull();

    // 3. Approve Leave Request
    $approveResponse = $this->withHeaders($headers)->patchJson("http://hrtest.localhost/api/hr/leave/requests/{$leaveRequestId}/approve", [
        'notes' => 'Approved by manager',
    ]);
    $approveResponse->assertStatus(200);
    $approveResponse->assertJsonPath('status', 'approved');

    // 4. Verify attendance log retrieval with date filtering
    $today = date('Y-m-d');
    $attendanceResponse = $this->withHeaders($headers)->getJson("http://hrtest.localhost/api/hr/attendance?start_date={$today}&end_date={$today}");
    $attendanceResponse->assertStatus(200);
    $attendanceResponse->assertJsonCount(1);
    $attendanceResponse->assertJsonPath('0.clock_type', 'in');
});
