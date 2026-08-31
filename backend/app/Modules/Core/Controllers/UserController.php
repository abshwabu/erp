<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('role') && $request->input('role') !== 'all') {
            $roleName = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        if ($request->filled('is_active') && $request->input('is_active') !== 'all') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'mfa_enabled' => $user->mfa_enabled,
                'last_login_at' => $user->last_login_at,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return $this->successResponse($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenantId = tenant()?->id;

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return $this->createdResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->fresh('roles')->roles->pluck('name'),
            'created_at' => $user->created_at,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with('roles.permissions')->findOrFail($id);

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'mfa_enabled' => $user->mfa_enabled,
            'last_login_at' => $user->last_login_at,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name')->unique()->values(),
            'created_at' => $user->created_at,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updateData = [];
        if (isset($validated['name'])) $updateData['name'] = $validated['name'];
        if (isset($validated['email'])) $updateData['email'] = $validated['email'];
        if (!empty($validated['password'])) $updateData['password'] = Hash::make($validated['password']);
        if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];

        $user->update($updateData);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->fresh('roles')->roles->pluck('name'),
            'updated_at' => $user->updated_at,
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        return $this->successResponse([
            'id' => $user->id,
            'is_active' => $user->is_active,
            'message' => $user->is_active ? 'User account activated.' : 'User account suspended.',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($request->user()?->id === $user->id) {
            return $this->errorResponse('You cannot delete your own active user account.', 400);
        }

        $user->delete();

        return $this->successResponse(['message' => 'User deleted successfully.']);
    }
}
