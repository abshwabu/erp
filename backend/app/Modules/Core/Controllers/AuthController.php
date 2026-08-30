<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Core\Requests\LoginRequest;
use App\Modules\Core\Requests\RegisterRequest;
use App\Modules\Core\Requests\ResetPasswordRequest;
use App\Modules\Core\Services\MfaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly MfaService $mfaService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        // 1. Create Tenant (triggers migration & seeding via TenancyServiceProvider)
        $tenant = Tenant::create([
            'name' => $request->company_name,
            'slug' => $request->domain,
            'status' => 'active',
            'settings' => [
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'currency' => config('app.currency', 'USD'),
            ],
        ]);

        // 2. Initialize Tenancy to interact with the new tenant DB
        tenancy()->initialize($tenant);

        // 3. Create the first User in the tenant's database
        $user = User::create([
            'tenant_id' => $tenant->getTenantKey(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        // 4. Assign Owner role (seeded by TenantRoleSeeder)
        $user->assignRole('Owner');

        // 5. Generate tokens for the new user
        $ttl = (int) config('jwt.ttl', 120);
        $accessToken = auth('api')
            ->claims(['type' => 'access', 'tenant_id' => $tenant->getTenantKey()])
            ->setTTL($ttl)
            ->login($user);

        $refreshToken = auth('api')
            ->claims(['type' => 'refresh', 'tenant_id' => $tenant->getTenantKey()])
            ->setTTL(43200)
            ->tokenById($user->id);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => $ttl * 60,
            'tenant' => [
                'id' => $tenant->getTenantKey(),
                'name' => $tenant->name,
                'domain' => $tenant->slug,
            ]
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = null;
        $tenant = null;

        if (tenancy()->initialized) {
            $tenant = tenancy()->tenant;
            $user = User::where('email', $request->email)->first();
        } else {
            $tenantId = $request->header('X-Tenant-ID');
            if ($tenantId) {
                $candidate = Tenant::query()
                    ->where(function ($q) use ($tenantId) {
                        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tenantId)) {
                            $q->where('id', $tenantId);
                        }
                        $q->orWhere('slug', $tenantId);
                    })
                    ->first();

                if ($candidate) {
                    $found = $candidate->run(fn () => User::where('email', $request->email)->first());
                    if ($found) {
                        $tenant = $candidate;
                        $user = $found;
                        tenancy()->initialize($tenant);
                    }
                }
            }

            if (! $user) {
                $tenants = Tenant::where('status', 'active')->get();
                foreach ($tenants as $t) {
                    try {
                        $found = $t->run(fn () => User::where('email', $request->email)->first());
                        if ($found) {
                            $tenant = $t;
                            $user = $found;
                            tenancy()->initialize($tenant);
                            break;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            }
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        if ($user->mfa_enabled) {
            // Generate a short-lived MFA challenge token
            $mfaToken = auth('api')
                ->claims(['type' => 'mfa', 'tenant_id' => $tenant?->getTenantKey()])
                ->setTTL(5)
                ->tokenById($user->id);

            return response()->json([
                'mfa_required' => true,
                'mfa_token' => $mfaToken,
            ]);
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        $ttl = (int) config('jwt.ttl', 120);
        $accessToken = auth('api')
            ->claims(['type' => 'access', 'tenant_id' => $tenant?->getTenantKey()])
            ->setTTL($ttl)
            ->login($user);

        $refreshToken = auth('api')
            ->claims(['type' => 'refresh', 'tenant_id' => $tenant?->getTenantKey()])
            ->setTTL(43200)
            ->tokenById($user->id);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => $ttl * 60,
            'tenant' => $tenant ? [
                'id' => $tenant->getTenantKey(),
                'name' => $tenant->name,
                'domain' => $tenant->slug,
            ] : null,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (! $refreshToken) {
            return response()->json([
                'message' => 'Refresh token is required.',
            ], 400);
        }

        try {
            $payload = auth('api')->setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return response()->json([
                    'message' => 'Invalid token type.',
                ], 401);
            }

            $tenantId = $payload->get('tenant_id');
            if ($tenantId && ! tenancy()->initialized) {
                $tenant = Tenant::query()
                    ->where(function ($q) use ($tenantId) {
                        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $tenantId)) {
                            $q->where('id', $tenantId);
                        }
                        $q->orWhere('slug', $tenantId);
                    })
                    ->first();

                if ($tenant) {
                    tenancy()->initialize($tenant);
                }
            }

            $userId = $payload->get('sub');
            $user = User::find($userId);

            if (! $user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 401);
            }

            if (! $user->is_active) {
                return response()->json([
                    'message' => 'User is inactive.',
                ], 403);
            }

            $ttl = (int) config('jwt.ttl', 120);
            $claims = ['type' => 'access'];
            $resolvedTenantId = $user->tenant_id ?? $tenantId;
            if ($resolvedTenantId) {
                $claims['tenant_id'] = $resolvedTenantId;
            }

            $newAccessToken = auth('api')
                ->claims($claims)
                ->setTTL($ttl)
                ->login($user);

            return response()->json([
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'message' => 'Invalid or expired refresh token.',
            ], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            auth('api')->logout();
        } catch (\Throwable $e) {
            // Token might be already invalid or missing, continue
        }

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'mfa_enabled' => $user->mfa_enabled,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Laravel built-in reset notification
            $user->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
        }

        return response()->json([
            'message' => 'If the email exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return response()->json([
                'message' => 'Invalid reset token.',
            ], 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'message' => 'Reset token has expired.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password does not match our records.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function enableMfa(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $secret = $this->mfaService->generateSecret();

        $user->update([
            'mfa_secret' => $secret,
            'mfa_enabled' => false, // Set to false until verified
        ]);

        $tenant = tenant();
        $issuer = $tenant ? $tenant->name : 'ERP';
        $qrCodeUri = $this->mfaService->getQrCodeUri($issuer, $user->email, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_code_uri' => $qrCodeUri,
        ]);
    }

    public function verifyMfa(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $user->mfa_secret) {
            return response()->json([
                'message' => 'MFA secret has not been generated.',
            ], 400);
        }

        $isValid = $this->mfaService->verifyCode($user->mfa_secret, $request->code);

        if (! $isValid) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 400);
        }

        $user->update([
            'mfa_enabled' => true,
        ]);

        return response()->json([
            'message' => 'MFA enabled successfully.',
        ]);
    }

    public function challengeMfa(Request $request): JsonResponse
    {
        $request->validate([
            'mfa_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        try {
            $payload = auth('api')->setToken($request->mfa_token)->getPayload();

            if ($payload->get('type') !== 'mfa') {
                return response()->json([
                    'message' => 'Invalid MFA token type.',
                ], 401);
            }

            $userId = $payload->get('sub');
            $user = User::find($userId);

            if (! $user || ! $user->mfa_enabled || ! $user->mfa_secret) {
                return response()->json([
                    'message' => 'MFA challenge cannot be completed.',
                ], 400);
            }

            $isValid = $this->mfaService->verifyCode($user->mfa_secret, $request->code);

            if (! $isValid) {
                return response()->json([
                    'message' => 'Invalid OTP code.',
                ], 401);
            }

            $user->update([
                'last_login_at' => now(),
            ]);

            $accessToken = auth('api')
                ->claims(['type' => 'access'])
                ->setTTL(15)
                ->login($user);

            $refreshToken = auth('api')
                ->claims(['type' => 'refresh'])
                ->setTTL(43200)
                ->tokenById($user->id);

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => 15 * 60,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'message' => 'Invalid or expired MFA token.',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
