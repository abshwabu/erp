<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\CRM\Models\Activity;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Lead;
use App\Modules\Sales\Models\Customer;
use Illuminate\Http\JsonResponse;

class CrmDashboardController extends BaseController
{
    public function stats(): JsonResponse
    {
        $activeDeals = Deal::whereNotIn('stage', ['won', 'lost'])->get();
        $totalPipelineValue = $activeDeals->sum('amount');

        $wonDeals = Deal::where('stage', 'won')->get();
        $wonDealsValue = $wonDeals->sum('amount');

        $closedDealsCount = Deal::whereIn('stage', ['won', 'lost'])->count();
        $winRate = $closedDealsCount > 0 ? round(($wonDeals->count() / $closedDealsCount) * 100, 1) : 0;

        $activeLeadsCount = Lead::whereIn('status', ['new', 'contacted', 'qualified'])->count();
        $totalCustomersCount = Customer::count();

        // Stage breakdown
        $stages = ['qualification', 'proposal', 'negotiation', 'won', 'lost'];
        $dealsByStage = [];
        foreach ($stages as $stage) {
            $stageDeals = Deal::where('stage', $stage)->get();
            $dealsByStage[$stage] = [
                'count' => $stageDeals->count(),
                'total_amount' => $stageDeals->sum('amount'),
            ];
        }

        // Recent Activities
        $recentActivities = Activity::with(['customer', 'deal', 'lead'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Top Opportunities
        $topDeals = Deal::with('customer')
            ->whereNotIn('stage', ['won', 'lost'])
            ->orderByDesc('amount')
            ->limit(5)
            ->get();

        return $this->successResponse([
            'total_pipeline_value' => $totalPipelineValue,
            'won_deals_value' => $wonDealsValue,
            'win_rate' => $winRate,
            'active_leads_count' => $activeLeadsCount,
            'total_customers_count' => $totalCustomersCount,
            'deals_by_stage' => $dealsByStage,
            'recent_activities' => $recentActivities,
            'top_deals' => $topDeals,
        ]);
    }
}
