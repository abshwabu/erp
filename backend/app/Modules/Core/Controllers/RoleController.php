<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Permission;
use App\Modules\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    // ── Role CRUD ────────────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get()->map(fn (Role $role) => [
            'id'          => $role->id,
            'name'        => $role->name,
            'guard_name'  => $role->guard_name,
            'permissions' => $role->permissions->pluck('name'),
        ]);

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
        ]);

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'api',
        ]);

        return response()->json([
            'data'    => $role,
            'message' => 'Role created.',
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json([
            'data' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'guard_name'  => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name,' . $role->id],
        ]);

        $role->update(['name' => $data['name']]);

        return response()->json(['data' => $role, 'message' => 'Role updated.']);
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }

    // ── Permission sync ───────────────────────────────────────────────────────

    /**
     * Replace all permissions on a role (sync).
     * Accepts a list of permission strings from the Permission enum.
     */
    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $allowed = Permission::allValues();

        $data = $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', $allowed)],
        ]);

        $role->syncPermissions($data['permissions']);
        $this->clearCache();

        return response()->json([
            'message'     => 'Permissions synced.',
            'permissions' => $role->fresh('permissions')->permissions->pluck('name'),
        ]);
    }

    // ── User ↔ Role assignment ────────────────────────────────────────────────

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'roles'   => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->assignRole($data['roles']);
        $this->clearCache();

        return response()->json([
            'message' => 'Role(s) assigned.',
            'roles'   => $user->fresh('roles')->getRoleNames(),
        ]);
    }

    public function revokeRole(User $user, Role $role): JsonResponse
    {
        $user->removeRole($role);
        $this->clearCache();

        return response()->json([
            'message' => 'Role revoked.',
            'roles'   => $user->fresh('roles')->getRoleNames(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function clearCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
