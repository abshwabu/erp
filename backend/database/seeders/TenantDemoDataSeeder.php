<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fetch demo users
        $adminUser = DB::table('users')->where('email', 'admin@example.com')->first()
            ?? DB::table('users')->first();
        $ownerUser = DB::table('users')->where('email', 'owner@example.com')->first() ?? $adminUser;
        $cashierUser = DB::table('users')->where('email', 'cashier@example.com')->first() ?? $adminUser;
        $accountantUser = DB::table('users')->where('email', 'accountant@example.com')->first() ?? $adminUser;
        $shopkeeperUser = DB::table('users')->where('email', 'shopkeeper@example.com')->first() ?? $adminUser;
        $warehouseUser = DB::table('users')->where('email', 'warehouse@example.com')->first() ?? $adminUser;

        if (! $adminUser) {
            return;
        }

        // 2. Warehouses & Stock Locations
        $warehouse1Id = (string) Str::uuid();
        $existingWh1 = DB::table('warehouses')->where('code', 'WH-ADDIS-01')->first();
        if ($existingWh1) {
            $warehouse1Id = $existingWh1->id;
        } else {
            DB::table('warehouses')->insert([
                'id' => $warehouse1Id,
                'name' => 'Central Logistics Warehouse (Addis Ababa)',
                'code' => 'WH-ADDIS-01',
                'address' => json_encode(['city' => 'Addis Ababa', 'subcity' => 'Bole', 'street' => 'Ring Road']),
                'type' => 'own',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $locMainStorageId = (string) Str::uuid();
        $existingLoc1 = DB::table('stock_locations')->where('warehouse_id', $warehouse1Id)->where('code', 'LOC-MAIN-01')->first();
        if ($existingLoc1) {
            $locMainStorageId = $existingLoc1->id;
        } else {
            DB::table('stock_locations')->insert([
                'id' => $locMainStorageId,
                'warehouse_id' => $warehouse1Id,
                'code' => 'LOC-MAIN-01',
                'name' => 'General Storage Bay A',
                'type' => 'storage',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $warehouse2Id = (string) Str::uuid();
        $existingWh2 = DB::table('warehouses')->where('code', 'WH-BOLE-02')->first();
        if ($existingWh2) {
            $warehouse2Id = $existingWh2->id;
        } else {
            DB::table('warehouses')->insert([
                'id' => $warehouse2Id,
                'name' => 'Bole Retail Store Warehouse',
                'code' => 'WH-BOLE-02',
                'address' => json_encode(['city' => 'Addis Ababa', 'subcity' => 'Bole', 'street' => 'Cameroon St']),
                'type' => 'own',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $locBoleStorageId = (string) Str::uuid();
        $existingLoc2 = DB::table('stock_locations')->where('warehouse_id', $warehouse2Id)->where('code', 'LOC-BOLE-STORE')->first();
        if ($existingLoc2) {
            $locBoleStorageId = $existingLoc2->id;
        } else {
            DB::table('stock_locations')->insert([
                'id' => $locBoleStorageId,
                'warehouse_id' => $warehouse2Id,
                'code' => 'LOC-BOLE-STORE',
                'name' => 'Bole Retail Shelf & Backroom',
                'type' => 'storage',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Units of Measure & Product Categories
        $uomPcsId = (string) Str::uuid();
        $existingUom = DB::table('units_of_measure')->where('symbol', 'pcs')->first();
        if ($existingUom) {
            $uomPcsId = $existingUom->id;
        } else {
            DB::table('units_of_measure')->insert([
                'id' => $uomPcsId,
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'type' => 'unit',
                'is_base' => true,
                'conversion_factor' => 1.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categories = [
            ['name' => 'Computers & Laptops', 'slug' => 'computers-laptops', 'description' => 'Business laptops, workstations, and high-performance desktops'],
            ['name' => 'Point of Sale Hardware', 'slug' => 'pos-hardware', 'description' => 'Thermal receipt printers, barcode scanners, and cash drawers'],
            ['name' => 'Office Furniture & Setup', 'slug' => 'office-furniture', 'description' => 'Ergonomic chairs, standing desks, and conference gear'],
            ['name' => 'Electronics & Accessories', 'slug' => 'electronics-accessories', 'description' => 'USB-C docks, chargers, network cables, and power units'],
            ['name' => 'Retail & Beverages', 'slug' => 'retail-beverages', 'description' => 'Specialty coffee beans, beverages, and packaged consumables'],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $existingCat = DB::table('product_categories')->where('slug', $cat['slug'])->first();
            if ($existingCat) {
                $categoryMap[$cat['slug']] = $existingCat->id;
            } else {
                $catId = (string) Str::uuid();
                DB::table('product_categories')->insert([
                    'id' => $catId,
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'description' => $cat['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $categoryMap[$cat['slug']] = $catId;
            }
        }

        // 4. Rich Product Catalog with Initial Stock & Movements
        $demoProducts = [
            [
                'name' => 'Dell Latitude 5440 i7 Laptop (16GB/512GB SSD)',
                'sku' => 'LAP-DELL-5440',
                'category_slug' => 'computers-laptops',
                'cost_price' => 6500000, // in cents (65,000 ETB)
                'selling_price' => 8500000, // in cents (85,000 ETB)
                'barcode' => '738291048201',
                'stock' => 25,
            ],
            [
                'name' => 'Apple MacBook Air M3 15" (16GB Unified / 512GB)',
                'sku' => 'LAP-MAC-M3',
                'category_slug' => 'computers-laptops',
                'cost_price' => 11000000,
                'selling_price' => 13500000,
                'barcode' => '738291048202',
                'stock' => 15,
            ],
            [
                'name' => 'Omni-Directional 2D POS Barcode Scanner (USB)',
                'sku' => 'POS-SCAN-2D',
                'category_slug' => 'pos-hardware',
                'cost_price' => 450000,
                'selling_price' => 750000,
                'barcode' => '738291048203',
                'stock' => 60,
            ],
            [
                'name' => 'Epson 80mm High-Speed Thermal Receipt Printer',
                'sku' => 'POS-PRN-80',
                'category_slug' => 'pos-hardware',
                'cost_price' => 800000,
                'selling_price' => 1250000,
                'barcode' => '738291048204',
                'stock' => 40,
            ],
            [
                'name' => 'Heavy-Duty RJ11 Electric Cash Drawer (5 Bills / 8 Coins)',
                'sku' => 'POS-DRW-HD',
                'category_slug' => 'pos-hardware',
                'cost_price' => 350000,
                'selling_price' => 580000,
                'barcode' => '738291048205',
                'stock' => 35,
            ],
            [
                'name' => 'Executive High-Back Ergonomic Mesh Chair',
                'sku' => 'FUR-CHR-ERG',
                'category_slug' => 'office-furniture',
                'cost_price' => 1400000,
                'selling_price' => 2100000,
                'barcode' => '738291048206',
                'stock' => 20,
            ],
            [
                'name' => 'Electric Dual-Motor Standing Desk (160x80cm Oak Top)',
                'sku' => 'FUR-DSK-STD',
                'category_slug' => 'office-furniture',
                'cost_price' => 2800000,
                'selling_price' => 3950000,
                'barcode' => '738291048207',
                'stock' => 12,
            ],
            [
                'name' => 'Universal USB-C Dual 4K Docking Station 100W PD',
                'sku' => 'ACC-DOCK-4K',
                'category_slug' => 'electronics-accessories',
                'cost_price' => 600000,
                'selling_price' => 920000,
                'barcode' => '738291048208',
                'stock' => 55,
            ],
            [
                'name' => 'Specialty Grade 1 Yirgacheffe Arabica Coffee (1kg)',
                'sku' => 'RET-COF-YRG',
                'category_slug' => 'retail-beverages',
                'cost_price' => 45000,
                'selling_price' => 85000,
                'barcode' => '738291048209',
                'stock' => 200,
            ],
            [
                'name' => 'Cat6 UTP Solid Pure Copper Network Cable (305m Drum)',
                'sku' => 'CAB-CAT6-305',
                'category_slug' => 'electronics-accessories',
                'cost_price' => 550000,
                'selling_price' => 890000,
                'barcode' => '738291048210',
                'stock' => 30,
            ],
        ];

        foreach ($demoProducts as $pData) {
            $existingProduct = DB::table('products')->where('sku', $pData['sku'])->first();
            $productId = $existingProduct ? $existingProduct->id : (string) Str::uuid();

            if (! $existingProduct) {
                DB::table('products')->insert([
                    'id' => $productId,
                    'name' => $pData['name'],
                    'slug' => Str::slug($pData['name']),
                    'sku' => $pData['sku'],
                    'category_id' => $categoryMap[$pData['category_slug']] ?? null,
                    'type' => 'stockable',
                    'status' => 'active',
                    'cost_price' => $pData['cost_price'],
                    'selling_price' => $pData['selling_price'],
                    'currency_code' => 'ETB',
                    'description' => $pData['name'] . ' - Enterprise verified stock item.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert Barcode
                DB::table('product_barcodes')->insert([
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'barcode' => $pData['barcode'],
                    'type' => 'EAN13',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create or update stock level
            $existingStock = DB::table('stock_levels')
                ->where('product_id', $productId)
                ->where('location_id', $locMainStorageId)
                ->first();

            if (! $existingStock) {
                DB::table('stock_levels')->insert([
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'location_id' => $locMainStorageId,
                    'quantity_on_hand' => $pData['stock'],
                    'quantity_committed' => 0,
                    'quantity_on_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Stock Movement entry for initial intake
                DB::table('stock_movements')->insert([
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'to_location_id' => $locMainStorageId,
                    'quantity' => $pData['stock'],
                    'type' => 'opening',
                    'reference_type' => 'initial_seed',
                    'unit_cost' => $pData['cost_price'],
                    'currency_code' => 'ETB',
                    'notes' => 'Opening inventory balance seeded for demo workspace.',
                    'user_id' => $adminUser->id,
                    'created_at' => now(),
                ]);
            }
        }

        // 5. Shops & Shop Keepers & POS Terminals
        $shop1Id = (string) Str::uuid();
        $existingShop1 = DB::table('shops')->where('code', 'SHOP-BOLE-01')->first();
        if ($existingShop1) {
            $shop1Id = $existingShop1->id;
        } else {
            DB::table('shops')->insert([
                'id' => $shop1Id,
                'name' => 'Bole Mega Mall Flagship Store',
                'code' => 'SHOP-BOLE-01',
                'is_active' => true,
                'stock_mode' => 'own',
                'warehouse_id' => $warehouse2Id,
                'stock_location_id' => $locBoleStorageId,
                'phone' => '+251 911 234 567',
                'address' => json_encode(['street' => 'Bole Medhanealem', 'city' => 'Addis Ababa', 'country' => 'Ethiopia']),
                'notes' => 'Primary high-traffic retail branch with 2 checkout lanes.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $shop2Id = (string) Str::uuid();
        $existingShop2 = DB::table('shops')->where('code', 'SHOP-KAZ-02')->first();
        if ($existingShop2) {
            $shop2Id = $existingShop2->id;
        } else {
            DB::table('shops')->insert([
                'id' => $shop2Id,
                'name' => 'Kazanchis Business Center Store',
                'code' => 'SHOP-KAZ-02',
                'is_active' => true,
                'stock_mode' => 'shared_warehouse',
                'warehouse_id' => $warehouse1Id,
                'stock_location_id' => $locMainStorageId,
                'phone' => '+251 922 765 432',
                'address' => json_encode(['street' => 'Kazanchis Tito St', 'city' => 'Addis Ababa', 'country' => 'Ethiopia']),
                'notes' => 'Corporate and express retail outlet.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign Shop Keepers & Cashiers
        if ($shopkeeperUser) {
            DB::table('shop_user')->updateOrInsert(
                ['shop_id' => $shop1Id, 'user_id' => $shopkeeperUser->id],
                ['id' => (string) Str::uuid(), 'role' => 'manager', 'created_at' => now(), 'updated_at' => now()]
            );
        }
        if ($cashierUser) {
            DB::table('shop_user')->updateOrInsert(
                ['shop_id' => $shop1Id, 'user_id' => $cashierUser->id],
                ['id' => (string) Str::uuid(), 'role' => 'keeper', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // POS Terminals
        $terminal1Id = (string) Str::uuid();
        $existingTerm1 = DB::table('pos_terminals')->where('name', 'Front Counter Terminal #1')->first();
        if ($existingTerm1) {
            $terminal1Id = $existingTerm1->id;
        } else {
            DB::table('pos_terminals')->insert([
                'id' => $terminal1Id,
                'name' => 'Front Counter Terminal #1',
                'location_id' => $locBoleStorageId,
                'shop_id' => $shop1Id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Open active POS session for cashier
        if ($cashierUser && ! DB::table('pos_sessions')->where('terminal_id', $terminal1Id)->where('status', 'open')->exists()) {
            DB::table('pos_sessions')->insert([
                'id' => (string) Str::uuid(),
                'terminal_id' => $terminal1Id,
                'shop_id' => $shop1Id,
                'cashier_id' => $cashierUser->id,
                'opened_at' => now()->subHours(3),
                'opening_cash_cents' => 500000, // 5,000 ETB float
                'status' => 'open',
                'created_at' => now()->subHours(3),
                'updated_at' => now(),
            ]);
        }

        // 6. HR Departments, Positions & Employee Directory
        $hrDepartments = [
            ['name' => 'Executive Management', 'code' => 'DEP-EXEC'],
            ['name' => 'Information Technology', 'code' => 'DEP-IT'],
            ['name' => 'Finance & Accounting', 'code' => 'DEP-FIN'],
            ['name' => 'Retail & POS Operations', 'code' => 'DEP-RET'],
            ['name' => 'Warehouse & Supply Chain', 'code' => 'DEP-LOG'],
        ];

        $depMap = [];
        foreach ($hrDepartments as $d) {
            $existingDep = DB::table('hr_departments')->where('code', $d['code'])->first();
            if ($existingDep) {
                $depMap[$d['code']] = $existingDep->id;
            } else {
                $depId = (string) Str::uuid();
                DB::table('hr_departments')->insert([
                    'id' => $depId,
                    'name' => $d['name'],
                    'code' => $d['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $depMap[$d['code']] = $depId;
            }
        }

        $hrPositions = [
            ['title' => 'Chief Executive Officer', 'dep' => 'DEP-EXEC'],
            ['title' => 'Lead ERP Systems Architect', 'dep' => 'DEP-IT'],
            ['title' => 'Senior Financial Controller', 'dep' => 'DEP-FIN'],
            ['title' => 'Bole Branch Store Manager', 'dep' => 'DEP-RET'],
            ['title' => 'Head Cashier Specialist', 'dep' => 'DEP-RET'],
            ['title' => 'Warehouse Logistics Supervisor', 'dep' => 'DEP-LOG'],
        ];

        $posMap = [];
        foreach ($hrPositions as $p) {
            $existingPos = DB::table('hr_positions')->where('title', $p['title'])->first();
            if ($existingPos) {
                $posMap[$p['title']] = $existingPos->id;
            } else {
                $posId = (string) Str::uuid();
                DB::table('hr_positions')->insert([
                    'id' => $posId,
                    'department_id' => $depMap[$p['dep']] ?? null,
                    'title' => $p['title'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $posMap[$p['title']] = $posId;
            }
        }

        $employeesData = [
            [
                'number' => 'EMP-0001',
                'first_name' => 'Sarah',
                'last_name' => 'Jenkins',
                'email' => 'owner@example.com',
                'phone' => '+251 911 000 001',
                'dep' => 'DEP-EXEC',
                'pos' => 'Chief Executive Officer',
                'user_id' => $ownerUser?->id,
            ],
            [
                'number' => 'EMP-0002',
                'first_name' => 'David',
                'last_name' => 'Miller',
                'email' => 'admin@example.com',
                'phone' => '+251 911 000 002',
                'dep' => 'DEP-IT',
                'pos' => 'Lead ERP Systems Architect',
                'user_id' => $adminUser?->id,
            ],
            [
                'number' => 'EMP-0003',
                'first_name' => 'Michael',
                'last_name' => 'Chang',
                'email' => 'accountant@example.com',
                'phone' => '+251 911 000 003',
                'dep' => 'DEP-FIN',
                'pos' => 'Senior Financial Controller',
                'user_id' => $accountantUser?->id,
            ],
            [
                'number' => 'EMP-0004',
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'shopkeeper@example.com',
                'phone' => '+251 911 000 004',
                'dep' => 'DEP-RET',
                'pos' => 'Bole Branch Store Manager',
                'user_id' => $shopkeeperUser?->id,
            ],
            [
                'number' => 'EMP-0005',
                'first_name' => 'Robert',
                'last_name' => 'Johnson',
                'email' => 'cashier@example.com',
                'phone' => '+251 911 000 005',
                'dep' => 'DEP-RET',
                'pos' => 'Head Cashier Specialist',
                'user_id' => $cashierUser?->id,
            ],
            [
                'number' => 'EMP-0006',
                'first_name' => 'Samuel',
                'last_name' => 'Alemayehu',
                'email' => 'warehouse@example.com',
                'phone' => '+251 911 000 006',
                'dep' => 'DEP-LOG',
                'pos' => 'Warehouse Logistics Supervisor',
                'user_id' => $warehouseUser?->id,
            ],
        ];

        foreach ($employeesData as $emp) {
            $existingEmp = DB::table('hr_employees')
                ->where('email', $emp['email'])
                ->orWhere('employee_number', $emp['number'])
                ->first();
            if (! $existingEmp) {
                DB::table('hr_employees')->insert([
                    'id' => (string) Str::uuid(),
                    'employee_number' => $emp['number'],
                    'user_id' => $emp['user_id'],
                    'first_name' => $emp['first_name'],
                    'last_name' => $emp['last_name'],
                    'email' => $emp['email'],
                    'phone' => $emp['phone'],
                    'department_id' => $depMap[$emp['dep']] ?? null,
                    'position_id' => $posMap[$emp['pos']] ?? null,
                    'start_date' => now()->subMonths(8)->format('Y-m-d'),
                    'status' => 'active',
                    'employment_type' => 'full_time',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 7. Customers, Invoices & Sales
        $customersData = [
            ['name' => 'Ethiopian Airlines Logistics Enterprise', 'email' => 'procurement@ethiopianairlines.com', 'phone' => '+251 116 658 000'],
            ['name' => 'Commercial Bank of Ethiopia (IT Dept)', 'email' => 'vendor-desk@cbe.com.et', 'phone' => '+251 115 515 004'],
            ['name' => 'Safaricom Telecommunications PLC', 'email' => 'enterprise-sales@safaricom.et', 'phone' => '+251 700 000 000'],
            ['name' => 'Dashen Bank Digital Center', 'email' => 'purchasing@dashenbanksc.com', 'phone' => '+251 115 180 300'],
        ];

        $customerMap = [];
        foreach ($customersData as $c) {
            $existingCust = DB::table('customers')->where('email', $c['email'])->first();
            if ($existingCust) {
                $customerMap[$c['email']] = $existingCust->id;
            } else {
                $custId = (string) Str::uuid();
                DB::table('customers')->insert([
                    'id' => $custId,
                    'name' => $c['name'],
                    'email' => $c['email'],
                    'phone' => $c['phone'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $customerMap[$c['email']] = $custId;
            }
        }

        // Seed Sample Invoices
        $invoicesData = [
            [
                'number' => 'INV-2026-0001',
                'customer_email' => 'procurement@ethiopianairlines.com',
                'subtotal' => 17000000,
                'tax' => 2550000,
                'total' => 19550000,
                'status' => 'paid',
                'issue_date' => now()->subDays(12)->format('Y-m-d'),
                'due_date' => now()->addDays(18)->format('Y-m-d'),
            ],
            [
                'number' => 'INV-2026-0002',
                'customer_email' => 'vendor-desk@cbe.com.et',
                'subtotal' => 8500000,
                'tax' => 1275000,
                'total' => 9775000,
                'status' => 'sent',
                'issue_date' => now()->subDays(3)->format('Y-m-d'),
                'due_date' => now()->addDays(27)->format('Y-m-d'),
            ],
        ];

        foreach ($invoicesData as $inv) {
            $existingInv = DB::table('invoices')->where('number', $inv['number'])->first();
            if (! $existingInv && isset($customerMap[$inv['customer_email']])) {
                $invId = (string) Str::uuid();
                DB::table('invoices')->insert([
                    'id' => $invId,
                    'customer_id' => $customerMap[$inv['customer_email']],
                    'number' => $inv['number'],
                    'status' => $inv['status'],
                    'subtotal_cents' => $inv['subtotal'],
                    'tax_cents' => $inv['tax'],
                    'total_cents' => $inv['total'],
                    'amount_paid_cents' => $inv['status'] === 'paid' ? $inv['total'] : 0,
                    'issue_date' => $inv['issue_date'],
                    'due_date' => $inv['due_date'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Add line items
                DB::table('invoice_lines')->insert([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invId,
                    'description' => 'Dell Latitude 5440 Core i7 Workstation Bundle',
                    'quantity' => 2,
                    'unit_price_cents' => 8500000,
                    'line_total_cents' => 17000000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 8. Suppliers & Purchase Orders
        $suppliersData = [
            ['name' => 'Addis Tech Supplies PLC', 'email' => 'sales@addistech.et', 'phone' => '+251 911 112 233'],
            ['name' => 'Global Electronics Distribution FZE', 'email' => 'orders@globalelec.com', 'phone' => '+971 4 881 2233'],
            ['name' => 'Ethio Ergonomic Office Furniture Ltd', 'email' => 'sales@ethiofurniture.et', 'phone' => '+251 912 334 455'],
        ];

        $supplierMap = [];
        foreach ($suppliersData as $s) {
            $existingSup = DB::table('suppliers')->where('email', $s['email'])->first();
            if ($existingSup) {
                $supplierMap[$s['email']] = $existingSup->id;
            } else {
                $supId = (string) Str::uuid();
                DB::table('suppliers')->insert([
                    'id' => $supId,
                    'name' => $s['name'],
                    'email' => $s['email'],
                    'phone' => $s['phone'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $supplierMap[$s['email']] = $supId;
            }
        }

        // Purchase Orders
        $po1Id = (string) Str::uuid();
        $existingPo1 = DB::table('purchase_orders')->where('number', 'PO-2026-001')->first();
        if (! $existingPo1 && isset($supplierMap['sales@addistech.et'])) {
            DB::table('purchase_orders')->insert([
                'id' => $po1Id,
                'supplier_id' => $supplierMap['sales@addistech.et'],
                'number' => 'PO-2026-001',
                'status' => 'received',
                'order_date' => now()->subDays(15)->format('Y-m-d'),
                'total_cents' => 162500000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('purchase_order_lines')->insert([
                'id' => (string) Str::uuid(),
                'purchase_order_id' => $po1Id,
                'description' => 'Dell Latitude 5440 i7 Laptops (Batch)',
                'quantity' => 25,
                'unit_cost_cents' => 6500000,
                'received_quantity' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 9. CRM Leads & Pipeline Deals
        $leadsData = [
            ['name' => 'Yohannes Bekele', 'email' => 'yohannes@midroc.et', 'company' => 'MIDROC Investment Group', 'status' => 'qualified', 'source' => 'website'],
            ['name' => 'Almaz Tesfaye', 'email' => 'almaz@ethiofin.com', 'company' => 'Ethio Capital Partners', 'status' => 'contacted', 'source' => 'referral'],
        ];

        foreach ($leadsData as $ld) {
            $existingLead = DB::table('crm_leads')->where('email', $ld['email'])->first();
            if (! $existingLead) {
                DB::table('crm_leads')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $ld['name'],
                    'email' => $ld['email'],
                    'company' => $ld['company'],
                    'status' => $ld['status'],
                    'source' => $ld['source'],
                    'assigned_to_user_id' => $adminUser->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 10. Projects, Milestones & Tasks
        $existingProj = DB::table('projects')->where('code', 'PRJ-2026-001')->first();
        $projectId = $existingProj ? $existingProj->id : (string) Str::uuid();

        if (! $existingProj) {
            DB::table('projects')->insert([
                'id' => $projectId,
                'code' => 'PRJ-2026-001',
                'name' => 'Enterprise Cloud ERP Deployment 2026',
                'description' => 'Multi-tenant cloud ERP migration, POS branch rollout, and double-entry accounting setup.',
                'status' => 'in_progress',
                'priority' => 'high',
                'start_date' => now()->subMonths(1)->format('Y-m-d'),
                'due_date' => now()->addMonths(2)->format('Y-m-d'),
                'manager_id' => $adminUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Milestones
            $m1Id = (string) Str::uuid();
            DB::table('project_milestones')->insert([
                'id' => $m1Id,
                'project_id' => $projectId,
                'title' => 'Phase 1: Catalog & Barcode Scanner Setup',
                'due_date' => now()->subDays(5)->format('Y-m-d'),
                'status' => 'achieved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $m2Id = (string) Str::uuid();
            DB::table('project_milestones')->insert([
                'id' => $m2Id,
                'project_id' => $projectId,
                'title' => 'Phase 2: Bole Store POS Cashier Training',
                'due_date' => now()->addDays(10)->format('Y-m-d'),
                'status' => 'in_progress',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Tasks
            DB::table('project_tasks')->insert([
                'id' => (string) Str::uuid(),
                'project_id' => $projectId,
                'milestone_id' => $m1Id,
                'title' => 'Verify thermal receipt printer auto-cutter',
                'status' => 'completed',
                'priority' => 'high',
                'assigned_to_user_id' => $adminUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('project_tasks')->insert([
                'id' => (string) Str::uuid(),
                'project_id' => $projectId,
                'milestone_id' => $m2Id,
                'title' => 'Run mock checkout and Z-report reconciliation shift',
                'status' => 'in_progress',
                'priority' => 'medium',
                'assigned_to_user_id' => $cashierUser?->id ?? $adminUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 11. Support Tickets
        $existingTicket = DB::table('support_tickets')->where('ticket_number', 'TCK-101')->first();
        if (! $existingTicket) {
            DB::table('support_tickets')->insert([
                'id' => (string) Str::uuid(),
                'ticket_number' => 'TCK-101',
                'subject' => 'Thermal Receipt Printer Setup Guide',
                'status' => 'open',
                'priority' => 'normal',
                'channel' => 'web',
                'assigned_to' => $adminUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
