<?php

declare(strict_types=1);

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSSessionController extends BaseController
{
    /**
     * GET /api/pos/terminals
     */
    public function terminals(): JsonResponse
    {
        $terminals = POSTerminal::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($terminals->isEmpty()) {
            $locationId = StockLocation::query()->where('is_active', true)->value('id')
                ?? StockLocation::query()->value('id');

            if ($locationId) {
                $terminal = POSTerminal::create([
                    'name' => 'Front Counter',
                    'location_id' => $locationId,
                    'is_active' => true,
                ]);
                $terminals = collect([$terminal]);
            }
        }

        return $this->successResponse($terminals);
    }

    /**
     * GET /api/pos/sessions/current
     */
    public function current(): JsonResponse
    {
        $session = POSSession::with('terminal')
            ->where('cashier_id', auth()->id())
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return $this->successResponse($session);
    }

    /**
     * POST /api/pos/sessions/open
     */
    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'terminal_id' => ['required', 'uuid', 'exists:pos_terminals,id'],
            'opening_cash_cents' => ['nullable', 'integer', 'min:0'],
        ]);

        $existing = POSSession::query()
            ->where('cashier_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $this->successResponse($existing->load('terminal'));
        }

        $openOnTerminal = POSSession::query()
            ->where('terminal_id', $data['terminal_id'])
            ->where('status', 'open')
            ->exists();

        if ($openOnTerminal) {
            return $this->errorResponse('This terminal already has an open session.', 422);
        }

        $session = POSSession::create([
            'terminal_id' => $data['terminal_id'],
            'cashier_id' => auth()->id(),
            'opened_at' => now(),
            'opening_cash_cents' => (int) ($data['opening_cash_cents'] ?? 0),
            'expected_cash_cents' => (int) ($data['opening_cash_cents'] ?? 0),
            'status' => 'open',
        ]);

        return $this->createdResponse($session->load('terminal'));
    }

    /**
     * POST /api/pos/sessions/{session}/close
     */
    public function close(Request $request, string $session): JsonResponse
    {
        $data = $request->validate([
            'closing_cash_cents' => ['required', 'integer', 'min:0'],
        ]);

        $posSession = POSSession::findOrFail($session);

        if ($posSession->status !== 'open') {
            return $this->errorResponse('Session is already closed.', 422);
        }

        if ((string) $posSession->cashier_id !== (string) auth()->id()) {
            return $this->errorResponse('You can only close your own session.', 403);
        }

        $posSession->update([
            'closed_at' => now(),
            'closing_cash_cents' => $data['closing_cash_cents'],
            'cash_variance_cents' => $data['closing_cash_cents'] - (int) $posSession->expected_cash_cents,
            'status' => 'closed',
        ]);

        return $this->successResponse($posSession->fresh('terminal'));
    }
}
