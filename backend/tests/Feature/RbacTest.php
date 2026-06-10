<?php

declare(strict_types=1);

use App\Modules\Core\Enums\Permission;
use App\Modules\Core\Http\Middleware\PermissionMiddleware;
use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ── Helper ────────────────────────────────────────────────────────────────────

/**
 * Create a tenant, seed roles/permissions, create a user, assign the given role.
 * Returns [$tenant, $user].
 */
function makeRbacUser(string $roleName): array
{
    $tenant = Tenant::create([
        'name'   => "RBAC {$roleName} Tenant",
        'slug'   => strtolower(str_replace(' ', '-', $roleName)) . '-' . str()->random(4),
        'status' => 'active',
    ]);

    $user = $tenant->run(function () use ($tenant, $roleName) {
        (new TenantRoleSeeder())->run();

        $user = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => "Test {$roleName}",
            'email'     => strtolower(str_replace(' ', '.', $roleName)) . '@rbac.test',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole($roleName);

        return $user;
    });

    return [$tenant, $user];
}

/**
 * Invoke PermissionMiddleware directly with the given user and permission string.
 * Must be called inside a $tenant->run() context so Spatie has access to the tenant DB.
 * Returns the HTTP response from the middleware.
 */
function checkPermission(User $user, string $permission): \Symfony\Component\HttpFoundation\Response
{
    $middleware = app(PermissionMiddleware::class);

    $request = Request::create('/stub', 'GET');
    $request->setUserResolver(fn () => $user);

    return $middleware->handle(
        $request,
        fn () => response()->json(['ok' => true]),
        $permission
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Cashier CAN access POS endpoints
// ─────────────────────────────────────────────────────────────────────────────

it('allows a Cashier to access POS endpoints', function (): void {
    [$tenant, $user] = makeRbacUser('Cashier');

    $tenant->run(function () use ($user) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        $response = checkPermission($user, Permission::PosTransactionsCreate->value);

        expect($response->getStatusCode())->toBe(200);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Cashier CANNOT access Accounting endpoints
// ─────────────────────────────────────────────────────────────────────────────

it('blocks a Cashier from accessing Accounting endpoints', function (): void {
    [$tenant, $user] = makeRbacUser('Cashier');

    $tenant->run(function () use ($user) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        $response = checkPermission($user, Permission::AccountingJournalsView->value);

        expect($response->getStatusCode())->toBe(403);

        $body = json_decode($response->getContent(), true);
        expect($body['required_permission'])->toBe(Permission::AccountingJournalsView->value);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Permission cache is cleared after a role permission sync
// ─────────────────────────────────────────────────────────────────────────────

it('clears the permission cache when role permissions are synced', function (): void {
    [$tenant, $user] = makeRbacUser('Cashier');

    $tenant->run(function () use ($user) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        // --- Cashier should NOT have accounting access initially ---
        expect($user->can(Permission::AccountingJournalsView->value))->toBeFalse();

        // --- Grant the permission to the Cashier role ---
        $cashierRole = Role::findByName('Cashier', 'api');
        $cashierRole->givePermissionTo(Permission::AccountingJournalsView->value);

        // Cache MUST be cleared for the change to be visible
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        expect($user->can(Permission::AccountingJournalsView->value))->toBeTrue();

        // --- Revert and verify cache is cleared again ---
        $cashierRole->revokePermissionTo(Permission::AccountingJournalsView->value);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        expect($user->can(Permission::AccountingJournalsView->value))->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. hasModuleAccess() helper
// ─────────────────────────────────────────────────────────────────────────────

it('correctly reports module access via hasModuleAccess()', function (): void {
    [$tenant, $user] = makeRbacUser('Cashier');

    $tenant->run(function () use ($user) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        expect($user->hasModuleAccess('pos'))->toBeTrue();
        expect($user->hasModuleAccess('accounting'))->toBeFalse();
        expect($user->hasModuleAccess('hr'))->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Owner has access to all modules
// ─────────────────────────────────────────────────────────────────────────────

it('grants Owner access to all modules', function (): void {
    [$tenant, $user] = makeRbacUser('Owner');

    $tenant->run(function () use ($user) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        foreach (['pos', 'accounting', 'hr', 'payroll', 'warehouse', 'inventory'] as $module) {
            expect($user->hasModuleAccess($module))->toBeTrue(
                "Owner should have access to module: {$module}"
            );
        }
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Admin does not have the two excluded permissions
// ─────────────────────────────────────────────────────────────────────────────

it('Admin is denied core.roles.delete and payroll.runs.process', function (): void {
    [$tenant, $user] = makeRbacUser('Admin');

    $tenant->run(function () use ($user) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        expect($user->can(Permission::CoreRolesDelete->value))->toBeFalse();
        expect($user->can(Permission::PayrollRunsProcess->value))->toBeFalse();

        // But Admin can do most other things
        expect($user->can(Permission::CoreUsersCreate->value))->toBeTrue();
        expect($user->can(Permission::AccountingJournalsPost->value))->toBeTrue();
    });
});
