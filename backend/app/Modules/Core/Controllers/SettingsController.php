<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseController
{
    public function show(): JsonResponse
    {
        $tenant = tenant();
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];

        return $this->successResponse([
            'display_name' => $settings['display_name'] ?? ($tenant?->name ?? ''),
            'timezone' => $settings['timezone'] ?? 'UTC',
            'currency' => $settings['currency'] ?? 'USD',
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'max:8'],
        ]);

        $tenant = tenant();
        if (! $tenant) {
            return $this->errorResponse('No active tenant.', 400);
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $settings = array_merge($settings, array_filter($validated, static fn ($v) => $v !== null));

        $tenant->update(['settings' => $settings]);

        return $this->successResponse([
            'display_name' => $settings['display_name'] ?? ($tenant->name ?? ''),
            'timezone' => $settings['timezone'] ?? 'UTC',
            'currency' => $settings['currency'] ?? 'USD',
        ]);
    }
}
