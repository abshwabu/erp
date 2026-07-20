<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Import Core models
use App\Modules\Core\Models\User;

// Import Inventory models
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockMovement;

// Import POS models
use App\Modules\POS\Models\POSTerminal;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSCashMovement;
use App\Modules\POS\Models\POSTransaction;
use App\Modules\POS\Models\POSTransactionItem;
use App\Modules\POS\Models\POSPayment;

// Import HR models
use App\Modules\HR\Models\Department;
use App\Modules\HR\Models\Position;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\WorkSchedule;
use App\Modules\HR\Models\EmployeeSchedule;
use App\Modules\HR\Models\AttendanceLog;
use App\Modules\HR\Models\LeaveType;
use App\Modules\HR\Models\LeaveEntitlement;
use App\Modules\HR\Models\LeaveRequest;

class TenantDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = function_exists('tenant') ? tenant('id') : Str::uuid()->toString();

        // 1. Seed Users and Assign Roles
        $password = bcrypt('password');

        $usersData = [
            'owner' => [
                'name' => 'Owner User',
                'email' => 'owner@erp.com',
                'password' => $password,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'role' => 'Owner',
            ],
            'admin' => [
                'name' => 'Admin User',
                'email' => 'admin@erp.com',
                'password' => $password,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'role' => 'Admin',
            ],
            'cashier' => [
                'name' => 'Cashier Jane',
                'email' => 'cashier@erp.com',
                'password' => $password,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'role' => 'Cashier',
            ],
            'warehouse' => [
                'name' => 'Warehouse John',
                'email' => 'warehouse@erp.com',
                'password' => $password,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'role' => 'Warehouse Staff',
            ],
            'hr' => [
                'name' => 'HR Officer Bob',
                'email' => 'hr@erp.com',
                'password' => $password,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'role' => 'HR Officer',
            ],
            'accountant' => [
                'name' => 'Accountant Johnson',
                'email' => 'accountant@erp.com',
                'password' => $password,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'role' => 'Accountant',
            ],
        ];

        $users = [];
        foreach ($usersData as $key => $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(['email' => $data['email']], $data);
            $user->assignRole($role);
            $users[$key] = $user;
        }

        // 2. Seed Inventory Categories & Products
        $electronics = ProductCategory::updateOrCreate(
            ['slug' => 'electronics'],
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories',
                'is_active' => true,
            ]
        );

        $officeSupplies = ProductCategory::updateOrCreate(
            ['slug' => 'office-supplies'],
            [
                'name' => 'Office Supplies',
                'description' => 'Pens, desks, chairs and other office equipment',
                'is_active' => true,
            ]
        );

        $laptop = Product::updateOrCreate(
            ['sku' => 'LAP-001'],
            [
                'category_id' => $electronics->id,
                'name' => 'Enterprise Laptop',
                'slug' => 'enterprise-laptop',
                'description' => 'High performance enterprise laptop',
                'short_description' => 'Core i7, 16GB RAM, 512GB SSD',
                'type' => \App\Modules\Inventory\Enums\ProductType::Stockable,
                'status' => \App\Modules\Inventory\Enums\ProductStatus::Active,
                'cost_price' => 50000, // $500.00
                'selling_price' => 80000, // $800.00
                'currency_code' => 'USD',
                'has_variants' => false,
                'track_serial_numbers' => false,
                'track_lots' => false,
            ]
        );

        $mouse = Product::updateOrCreate(
            ['sku' => 'MOU-002'],
            [
                'category_id' => $electronics->id,
                'name' => 'Wireless Mouse',
                'slug' => 'wireless-mouse',
                'description' => 'Ergonomic wireless mouse',
                'short_description' => '2.4GHz with nano receiver',
                'type' => \App\Modules\Inventory\Enums\ProductType::Stockable,
                'status' => \App\Modules\Inventory\Enums\ProductStatus::Active,
                'cost_price' => 1000, // $10.00
                'selling_price' => 2500, // $25.00
                'currency_code' => 'USD',
                'has_variants' => false,
                'track_serial_numbers' => false,
                'track_lots' => false,
            ]
        );

        $desk = Product::updateOrCreate(
            ['sku' => 'DSK-003'],
            [
                'category_id' => $officeSupplies->id,
                'name' => 'Adjustable Office Desk',
                'slug' => 'adjustable-office-desk',
                'description' => 'Ergonomic standing desk with memory settings',
                'short_description' => 'Adjustable height standing desk',
                'type' => \App\Modules\Inventory\Enums\ProductType::Stockable,
                'status' => \App\Modules\Inventory\Enums\ProductStatus::Active,
                'cost_price' => 15000, // $150.00
                'selling_price' => 25000, // $250.00
                'currency_code' => 'USD',
                'has_variants' => false,
                'track_serial_numbers' => false,
                'track_lots' => false,
            ]
        );

        // 3. Seed Warehouses & Stock Locations
        $warehouseMain = Warehouse::updateOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Main Central Warehouse',
                'type' => 'own',
                'is_active' => true,
                'address' => json_encode([
                    'street' => '123 Logistics Way',
                    'city' => 'Metropolis',
                    'country' => 'USA',
                ]),
            ]
        );

        $locationReceive = StockLocation::updateOrCreate(
            ['code' => 'WH-MAIN-REC'],
            [
                'warehouse_id' => $warehouseMain->id,
                'name' => 'Receiving Dock',
                'type' => 'receive',
                'is_active' => true,
            ]
        );

        $locationStorage = StockLocation::updateOrCreate(
            ['code' => 'WH-MAIN-STO'],
            [
                'warehouse_id' => $warehouseMain->id,
                'name' => 'General Storage Shelf A',
                'type' => 'storage',
                'is_active' => true,
            ]
        );

        // 4. Seed Stock Levels & Movements
        $stockProducts = [
            ['product' => $laptop, 'qty' => 50],
            ['product' => $mouse, 'qty' => 200],
            ['product' => $desk, 'qty' => 20],
        ];

        foreach ($stockProducts as $sp) {
            $prod = $sp['product'];
            $qty = $sp['qty'];

            StockLevel::updateOrCreate(
                [
                    'product_id' => $prod->id,
                    'location_id' => $locationStorage->id,
                ],
                [
                    'quantity_on_hand' => $qty,
                    'quantity_committed' => 0,
                    'quantity_on_order' => 0,
                ]
            );

            StockMovement::create([
                'product_id' => $prod->id,
                'to_location_id' => $locationStorage->id,
                'quantity' => $qty,
                'type' => 'opening',
                'reference_type' => 'InitialSeeding',
                'unit_cost' => $prod->cost_price,
                'currency_code' => 'USD',
                'notes' => 'Initial stock seeding for testing',
                'user_id' => $users['admin']->id,
            ]);
        }

        // 5. Seed POS Terminals, Sessions, Cash Movements, Transactions & Payments
        $terminal = POSTerminal::updateOrCreate(
            ['name' => 'Main POS Register'],
            [
                'location_id' => $warehouseMain->id,
                'is_active' => true,
                'receipt_printer_settings' => ['type' => 'thermal', 'width' => '80mm'],
                'cash_drawer_settings' => ['port' => 'DK', 'kick_code' => '27,112,0,25,250'],
            ]
        );

        $session = POSSession::create([
            'terminal_id' => $terminal->id,
            'cashier_id' => $users['cashier']->id,
            'opened_at' => Carbon::now()->subHours(2),
            'opening_cash_cents' => 10000, // $100.00
            'status' => 'open',
        ]);

        POSCashMovement::create([
            'session_id' => $session->id,
            'type' => 'cash_in',
            'amount_cents' => 5000, // $50.00 petty cash
            'reason' => 'Opening float adjustment',
            'user_id' => $users['cashier']->id,
            'created_at' => Carbon::now()->subHours(2)->addMinutes(5),
        ]);

        // POSTransaction 1 (Cash Sale)
        $tx1 = POSTransaction::create([
            'session_id' => $session->id,
            'subtotal_cents' => 82500, // $825.00
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 82500,
            'currency_code' => 'USD',
            'status' => 'completed',
            'receipt_number' => 'R-'.Str::random(8),
            'created_at' => Carbon::now()->subHours(1),
        ]);

        POSTransactionItem::create([
            'transaction_id' => $tx1->id,
            'product_id' => $laptop->id,
            'quantity' => 1.00,
            'unit_price_cents' => 80000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 80000,
        ]);

        POSTransactionItem::create([
            'transaction_id' => $tx1->id,
            'product_id' => $mouse->id,
            'quantity' => 1.00,
            'unit_price_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 2500,
        ]);

        POSPayment::create([
            'transaction_id' => $tx1->id,
            'method' => 'cash',
            'amount_cents' => 90000, // Cash paid $900.00
            'change_cents' => 7500, // Change given $75.00
            'processed_at' => Carbon::now()->subHours(1),
        ]);

        // POSTransaction 2 (Card Sale)
        $tx2 = POSTransaction::create([
            'session_id' => $session->id,
            'subtotal_cents' => 5000, // $50.00
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 5000,
            'currency_code' => 'USD',
            'status' => 'completed',
            'receipt_number' => 'R-'.Str::random(8),
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        POSTransactionItem::create([
            'transaction_id' => $tx2->id,
            'product_id' => $mouse->id,
            'quantity' => 2.00,
            'unit_price_cents' => 2500,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 5000,
        ]);

        POSPayment::create([
            'transaction_id' => $tx2->id,
            'method' => 'card',
            'amount_cents' => 5000,
            'reference' => 'TXN-CARD-123456',
            'change_cents' => 0,
            'processed_at' => Carbon::now()->subMinutes(30),
        ]);

        // 6. Seed HR Departments, Positions, Employees, Attendance, Leaves
        $deptOps = Department::updateOrCreate(
            ['code' => 'OPS'],
            [
                'name' => 'Operations Department',
            ]
        );

        $deptFinance = Department::updateOrCreate(
            ['code' => 'FIN'],
            [
                'name' => 'Finance & Accounting',
            ]
        );

        $posMgr = Position::updateOrCreate(
            ['title' => 'Inventory Manager'],
            [
                'department_id' => $deptOps->id,
                'job_grade' => 'M1',
                'min_salary_cents' => 300000, // $3000.00
                'max_salary_cents' => 500000, // $5000.00
                'description' => 'Manages warehouse staff and inventory stock levels',
                'is_active' => true,
            ]
        );

        $posCashier = Position::updateOrCreate(
            ['title' => 'Retail Cashier'],
            [
                'department_id' => $deptOps->id,
                'job_grade' => 'S1',
                'min_salary_cents' => 150000, // $1500.00
                'max_salary_cents' => 250000, // $2500.00
                'description' => 'Operates POS registers and processes customer payments',
                'is_active' => true,
            ]
        );

        $posAccountant = Position::updateOrCreate(
            ['title' => 'Senior Accountant'],
            [
                'department_id' => $deptFinance->id,
                'job_grade' => 'P3',
                'min_salary_cents' => 400000, // $4000.00
                'max_salary_cents' => 600000, // $6000.00
                'description' => 'Manages general ledger, journals and financial reporting',
                'is_active' => true,
            ]
        );

        $empWarehouse = Employee::updateOrCreate(
            ['email' => 'warehouse@erp.com'],
            [
                'employee_number' => 'EMP-001',
                'user_id' => $users['warehouse']->id,
                'department_id' => $deptOps->id,
                'position_id' => $posMgr->id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'preferred_name' => 'Johnny',
                'phone' => '+155501001',
                'date_of_birth' => '1990-05-15',
                'gender' => 'male',
                'employment_type' => 'full_time',
                'status' => 'active',
                'start_date' => Carbon::now()->subYear()->toDateString(),
            ]
        );

        $empCashier = Employee::updateOrCreate(
            ['email' => 'cashier@erp.com'],
            [
                'employee_number' => 'EMP-002',
                'user_id' => $users['cashier']->id,
                'department_id' => $deptOps->id,
                'position_id' => $posCashier->id,
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'preferred_name' => 'Jane',
                'phone' => '+155501002',
                'date_of_birth' => '1993-08-20',
                'gender' => 'female',
                'employment_type' => 'full_time',
                'status' => 'active',
                'start_date' => Carbon::now()->subMonths(6)->toDateString(),
            ]
        );

        $empAccountant = Employee::updateOrCreate(
            ['email' => 'accountant@erp.com'],
            [
                'employee_number' => 'EMP-003',
                'user_id' => $users['accountant']->id,
                'department_id' => $deptFinance->id,
                'position_id' => $posAccountant->id,
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'preferred_name' => 'Bob',
                'phone' => '+155501003',
                'date_of_birth' => '1988-11-02',
                'gender' => 'male',
                'employment_type' => 'full_time',
                'status' => 'active',
                'start_date' => Carbon::now()->subYears(2)->toDateString(),
            ]
        );

        // Work Schedules & Employee Schedules
        $schedule = WorkSchedule::updateOrCreate(
            ['name' => 'Standard Day Shift'],
            [
                'type' => 'fixed',
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'hours_per_day' => 8,
                'is_active' => true,
            ]
        );

        EmployeeSchedule::updateOrCreate(
            [
                'employee_id' => $empCashier->id,
                'schedule_id' => $schedule->id,
            ],
            [
                'effective_from' => Carbon::now()->subMonths(3)->toDateString(),
            ]
        );

        // Attendance Logs
        AttendanceLog::create([
            'employee_id' => $empCashier->id,
            'clock_type' => 'clock_in',
            'logged_at' => Carbon::today()->setHour(8)->setMinute(2)->toDateString(),
            'method' => 'web_portal',
            'notes' => 'On time clock in',
        ]);

        AttendanceLog::create([
            'employee_id' => $empCashier->id,
            'clock_type' => 'clock_out',
            'logged_at' => Carbon::today()->setHour(17)->setMinute(0)->toDateString(),
            'method' => 'web_portal',
            'notes' => 'Standard shift completion',
        ]);

        // Leaves
        $leaveType = LeaveType::updateOrCreate(
            ['code' => 'AL'],
            [
                'name' => 'Annual Leave',
                'is_paid' => true,
                'max_days_per_year' => 20,
                'carry_over_days' => 5,
                'requires_approval' => true,
                'requires_document' => false,
                'is_active' => true,
            ]
        );

        LeaveEntitlement::updateOrCreate(
            [
                'employee_id' => $empCashier->id,
                'leave_type_id' => $leaveType->id,
                'year' => Carbon::now()->year,
            ],
            [
                'entitled_days' => 20,
                'accrued_days' => 20,
                'taken_days' => 0,
                'carried_over_days' => 0,
            ]
        );

        LeaveRequest::create([
            'employee_id' => $empCashier->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::now()->addMonth()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->addMonth()->startOfMonth()->addDays(2)->toDateString(),
            'days_taken' => 3,
            'status' => 'pending',
            'reason' => 'Family vacation trip',
            'requested_at' => Carbon::now(),
        ]);
    }
}
