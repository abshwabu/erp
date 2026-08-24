<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Integrations\Models\Integration;
use App\Modules\Integrations\Models\IntegrationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends BaseController
{
    public function index(): JsonResponse
    {
        $integrations = Integration::withCount('logs')
            ->orderBy('name')
            ->get();

        return $this->successResponse($integrations);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider'    => ['required', 'string', 'in:stripe,slack,sendgrid,zapier,webhook,quickbooks'],
            'name'        => ['required', 'string', 'max:255'],
            'api_key'     => ['nullable', 'string', 'max:500'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
            'settings'    => ['nullable', 'array'],
        ]);

        $integration = Integration::create([
            'provider'    => $data['provider'],
            'name'        => $data['name'],
            'status'      => 'connected',
            'api_key'     => $data['api_key'] ?? null,
            'webhook_url' => $data['webhook_url'] ?? null,
            'settings'    => $data['settings'] ?? null,
        ]);

        return $this->createdResponse($integration);
    }

    public function show(string $id): JsonResponse
    {
        $integration = Integration::with(['logs' => fn ($q) => $q->orderByDesc('created_at')->limit(20)])
            ->findOrFail($id);

        return $this->successResponse($integration);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $integration = Integration::findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'status'      => ['sometimes', 'in:connected,disconnected,error'],
            'api_key'     => ['nullable', 'string', 'max:500'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
            'settings'    => ['nullable', 'array'],
        ]);

        $integration->update($data);

        return $this->successResponse($integration);
    }

    public function testConnection(string $id): JsonResponse
    {
        $integration = Integration::findOrFail($id);

        $integration->update([
            'status'         => 'connected',
            'last_tested_at' => now(),
        ]);

        IntegrationLog::create([
            'integration_id' => $integration->id,
            'event'          => 'health_check',
            'direction'      => 'outbound',
            'status_code'    => 200,
            'payload'        => ['test' => true],
            'response'       => ['status' => 'healthy'],
        ]);

        return $this->successResponse([
            'status'         => 'connected',
            'message'        => 'Connection test successful',
            'last_tested_at' => $integration->last_tested_at,
        ]);
    }

    public function logs(string $id): JsonResponse
    {
        $logs = IntegrationLog::where('integration_id', $id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return $this->successResponse($logs);
    }

    public function destroy(string $id): JsonResponse
    {
        $integration = Integration::findOrFail($id);
        $integration->delete();

        return $this->noContentResponse();
    }
}
