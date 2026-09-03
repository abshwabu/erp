<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Services\ModuleManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * List all available ERP modules, their dependency relations, and active state.
     */
    public function index(): JsonResponse
    {
        /** @var Tenant|null $tenant */
        $tenant = tenant();
        $plan = $tenant?->plan;
        $planAllowed = $plan?->getAllowedModules() ?? ['*'];
        $enabledModules = $tenant ? $tenant->getEnabledModules() : array_keys(ModuleManager::MODULES);

        $modules = [];
        foreach (ModuleManager::MODULES as $key => $mod) {
            $isAllowedByPlan = in_array('*', $planAllowed, true) || in_array($key, $planAllowed, true);
            $isEnabled = in_array($key, $enabledModules, true);
            $prereqs = ModuleManager::getPrerequisites($key);
            $dependents = ModuleManager::getDependents($key);

            $modules[] = [
                'key'             => $key,
                'name'            => $mod['name'],
                'category'        => $mod['category'],
                'description'     => $mod['description'],
                'icon'            => $mod['icon'],
                'dependencies'    => $prereqs,
                'direct_deps'     => $mod['dependencies'],
                'dependents'      => $dependents,
                'allowed_by_plan' => $isAllowedByPlan,
                'is_enabled'      => $isEnabled,
                'is_core'         => $key === 'core',
            ];
        }

        return response()->json([
            'data' => [
                'modules'         => $modules,
                'enabled_modules' => $enabledModules,
                'plan' => [
                    'name'            => $plan?->name ?? 'Enterprise',
                    'slug'            => $plan?->slug ?? 'enterprise',
                    'allowed_modules' => $planAllowed,
                ],
            ],
        ]);
    }

    /**
     * Toggle an individual module ON or OFF with recursive dependency cascade.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'module'  => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);

        /** @var Tenant|null $tenant */
        $tenant = tenant();
        if (!$tenant) {
            return response()->json(['message' => 'Active tenant context required.'], 404);
        }

        $moduleKey = strtolower($request->input('module'));
        if (!isset(ModuleManager::MODULES[$moduleKey])) {
            return response()->json(['message' => "Module '{$moduleKey}' is not recognized."], 404);
        }

        $targetState = (bool) $request->input('enabled');

        // Cannot turn off Core
        if ($moduleKey === 'core' && !$targetState) {
            return response()->json(['message' => 'Core Settings & RBAC is fundamental and cannot be disabled.'], 422);
        }

        // Check plan entitlement
        if ($targetState && !$tenant->hasModuleAccess($moduleKey)) {
            $planName = $tenant->plan?->name ?? 'Current Plan';
            return response()->json([
                'message' => "The '{$moduleKey}' module is not included in your {$planName}. Please upgrade your subscription plan to unlock it.",
                'code' => 'PLAN_UPGRADE_REQUIRED',
            ], 403);
        }

        $resolution = $tenant->toggleModule($moduleKey, $targetState);
        $moduleMeta = ModuleManager::MODULES[$moduleKey];

        // Format friendly feedback message
        if ($targetState) {
            $activatedOther = array_diff($resolution['activated'], [$moduleKey]);
            if (!empty($activatedOther)) {
                $otherNames = array_map(fn ($k) => ModuleManager::MODULES[$k]['name'] ?? $k, $activatedOther);
                $msg = "Activated {$moduleMeta['name']}. Also automatically activated required modules: " . implode(', ', $otherNames) . '.';
            } else {
                $msg = "Activated {$moduleMeta['name']}.";
            }
        } else {
            $deactivatedOther = array_diff($resolution['deactivated'], [$moduleKey]);
            if (!empty($deactivatedOther)) {
                $otherNames = array_map(fn ($k) => ModuleManager::MODULES[$k]['name'] ?? $k, $deactivatedOther);
                $msg = "Deactivated {$moduleMeta['name']}. Also automatically deactivated dependent modules: " . implode(', ', $otherNames) . '.';
            } else {
                $msg = "Deactivated {$moduleMeta['name']}.";
            }
        }

        return response()->json([
            'message' => $msg,
            'data'    => [
                'enabled_modules' => $resolution['enabled'],
                'activated'       => $resolution['activated'],
                'deactivated'     => $resolution['deactivated'],
            ],
        ]);
    }
}
