<?php

declare(strict_types=1);

namespace App\Modules\Assets\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Assets\Models\Asset;
use App\Modules\Assets\Models\AssetDepreciation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Asset::with('assignee:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $assets = $query->paginate(25);

        return $this->successResponse($assets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'category'            => ['sometimes', 'in:equipment,machinery,vehicle,electronics,furniture,building'],
            'serial_number'       => ['nullable', 'string', 'max:100'],
            'purchase_date'       => ['nullable', 'date'],
            'purchase_cost_cents' => ['required', 'integer', 'min:0'],
            'salvage_value_cents' => ['sometimes', 'integer', 'min:0'],
            'useful_life_years'   => ['sometimes', 'integer', 'min:1'],
            'depreciation_method' => ['sometimes', 'in:straight_line,declining_balance'],
            'status'              => ['sometimes', 'in:active,maintenance,disposed,retired'],
            'assigned_to'         => ['nullable', 'uuid', 'exists:users,id'],
            'notes'               => ['nullable', 'string'],
        ]);

        $asset = Asset::create([
            'asset_tag'           => Asset::nextTag(),
            'name'                => $data['name'],
            'category'            => $data['category'] ?? 'equipment',
            'serial_number'       => $data['serial_number'] ?? null,
            'purchase_date'       => $data['purchase_date'] ?? null,
            'purchase_cost_cents' => $data['purchase_cost_cents'],
            'salvage_value_cents' => $data['salvage_value_cents'] ?? 0,
            'useful_life_years'   => $data['useful_life_years'] ?? 5,
            'depreciation_method' => $data['depreciation_method'] ?? 'straight_line',
            'status'              => $data['status'] ?? 'active',
            'assigned_to'         => $data['assigned_to'] ?? null,
            'notes'               => $data['notes'] ?? null,
        ]);

        return $this->createdResponse($asset->load('assignee:id,name'));
    }

    public function show(string $id): JsonResponse
    {
        $asset = Asset::with(['assignee:id,name,email', 'depreciations' => fn ($q) => $q->orderBy('fiscal_year')])
            ->findOrFail($id);

        return $this->successResponse($asset);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'category'    => ['sometimes', 'in:equipment,machinery,vehicle,electronics,furniture,building'],
            'status'      => ['sometimes', 'in:active,maintenance,disposed,retired'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'notes'       => ['nullable', 'string'],
        ]);

        $asset->update($data);

        return $this->successResponse($asset);
    }

    public function generateDepreciationSchedule(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $cost = $asset->purchase_cost_cents;
        $salvage = $asset->salvage_value_cents;
        $years = $asset->useful_life_years;
        $depreciableAmount = max(0, $cost - $salvage);
        $annualDepreciation = (int) floor($depreciableAmount / $years);

        AssetDepreciation::where('asset_id', $asset->id)->delete();

        $currentYear = (int) date('Y');
        $accumulated = 0;

        for ($i = 1; $i <= $years; $i++) {
            $year = $currentYear + $i - 1;
            $depAmount = ($i === $years) ? ($depreciableAmount - $accumulated) : $annualDepreciation;
            $accumulated += $depAmount;
            $bookValue = $cost - $accumulated;

            AssetDepreciation::create([
                'asset_id'                       => $asset->id,
                'fiscal_year'                    => $year,
                'depreciation_amount_cents'      => $depAmount,
                'accumulated_depreciation_cents' => $accumulated,
                'book_value_cents'               => $bookValue,
            ]);
        }

        return $this->successResponse($asset->load('depreciations'));
    }

    public function destroy(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return $this->noContentResponse();
    }
}
