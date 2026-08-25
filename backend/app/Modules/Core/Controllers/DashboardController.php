<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Ecommerce\Models\EcommerceOrder;
use App\Modules\Ecommerce\Models\Storefront;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\LeaveRequest;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTransaction;
use App\Modules\Projects\Models\Project;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Support\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    public function stats(): JsonResponse
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfPrevMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfPrevMonth = $now->copy()->subMonth()->endOfMonth();
        $startOfToday = $now->copy()->startOfDay();

        // 1. Revenue calculations (POS + Invoices + Ecommerce)
        $posThisMonth = (int) (POSTransaction::where('created_at', '>=', $startOfMonth)
            ->where('status', 'completed')
            ->sum('total_cents') ?? 0);

        $invoicesThisMonth = (int) (Invoice::where('created_at', '>=', $startOfMonth)
            ->where('status', 'paid')
            ->sum('total_cents') ?? 0);

        $ecomThisMonth = (int) (EcommerceOrder::where('created_at', '>=', $startOfMonth)
            ->where('payment_status', 'paid')
            ->sum('total_cents') ?? 0);

        $totalRevenueThisMonthCents = $posThisMonth + $invoicesThisMonth + $ecomThisMonth;

        $posPrevMonth = (int) (POSTransaction::whereBetween('created_at', [$startOfPrevMonth, $endOfPrevMonth])
            ->where('status', 'completed')
            ->sum('total_cents') ?? 0);

        $invoicesPrevMonth = (int) (Invoice::whereBetween('created_at', [$startOfPrevMonth, $endOfPrevMonth])
            ->where('status', 'paid')
            ->sum('total_cents') ?? 0);

        $ecomPrevMonth = (int) (EcommerceOrder::whereBetween('created_at', [$startOfPrevMonth, $endOfPrevMonth])
            ->where('payment_status', 'paid')
            ->sum('total_cents') ?? 0);

        $totalRevenuePrevMonthCents = $posPrevMonth + $invoicesPrevMonth + $ecomPrevMonth;

        $growthPct = 0;
        if ($totalRevenuePrevMonthCents > 0) {
            $growthPct = round((($totalRevenueThisMonthCents - $totalRevenuePrevMonthCents) / $totalRevenuePrevMonthCents) * 100, 1);
        }

        // Today's Sales
        $posToday = (int) (POSTransaction::where('created_at', '>=', $startOfToday)
            ->where('status', 'completed')
            ->sum('total_cents') ?? 0);
        $invoicesToday = (int) (Invoice::where('created_at', '>=', $startOfToday)
            ->where('status', 'paid')
            ->sum('total_cents') ?? 0);
        $ecomToday = (int) (EcommerceOrder::where('created_at', '>=', $startOfToday)
            ->where('payment_status', 'paid')
            ->sum('total_cents') ?? 0);
        $todayRevenueCents = $posToday + $invoicesToday + $ecomToday;

        $todayOrdersCount = POSTransaction::where('created_at', '>=', $startOfToday)->count()
            + Invoice::where('created_at', '>=', $startOfToday)->count()
            + EcommerceOrder::where('created_at', '>=', $startOfToday)->count();

        // 2. Inventory stats
        $totalProducts = Product::count();
        $products = Product::with('stockLevels')->get();

        $lowStockItems = [];
        $optimalStockCount = 0;
        $outOfStockCount = 0;
        $totalValuationCents = 0;

        foreach ($products as $p) {
            $currentStock = (int) ($p->stockLevels?->sum('quantity_on_hand') ?? 0);
            $minStock = 5;
            $sellingPriceCents = (int) ($p->selling_price ?? 0);

            $totalValuationCents += ($currentStock * $sellingPriceCents);

            if ($currentStock <= 0) {
                $outOfStockCount++;
                $lowStockItems[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'available_quantity' => 0,
                    'min_quantity' => $minStock,
                ];
            } elseif ($currentStock <= $minStock) {
                $lowStockItems[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'available_quantity' => $currentStock,
                    'min_quantity' => $minStock,
                ];
            } else {
                $optimalStockCount++;
            }
        }

        // 3. Team & Operations stats
        $employeeCount = Employee::where('status', 'active')->count();
        $pendingLeavesCount = LeaveRequest::where('status', 'pending')->count();
        $openTicketsCount = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
        $activeProjectsCount = Project::where('status', 'active')->count();
        $openPosSessionsCount = POSSession::where('status', 'open')->count();
        $activeStorefrontsCount = Storefront::where('is_published', true)->count();

        // 4. Last 6 Months Revenue Trend
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = $now->copy()->subMonths($i)->startOfMonth();
            $mEnd = $now->copy()->subMonths($i)->endOfMonth();
            $monthLabel = $mStart->format('M');

            $mPos = (int) (POSTransaction::whereBetween('created_at', [$mStart, $mEnd])->where('status', 'completed')->sum('total_cents') ?? 0);
            $mInv = (int) (Invoice::whereBetween('created_at', [$mStart, $mEnd])->where('status', 'paid')->sum('total_cents') ?? 0);
            $mEcom = (int) (EcommerceOrder::whereBetween('created_at', [$mStart, $mEnd])->where('payment_status', 'paid')->sum('total_cents') ?? 0);
            $mRev = ($mPos + $mInv + $mEcom) / 100;

            $monthlyTrend[] = [
                'month' => $monthLabel,
                'revenue' => $mRev,
            ];
        }

        // 5. Recent Stock Movements
        $recentMovements = StockMovement::with('product:id,name,sku')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'product_name' => $m->product?->name ?? 'Product',
                'sku' => $m->product?->sku ?? 'N/A',
                'type' => $m->type ?? 'adjustment',
                'quantity' => $m->quantity,
                'created_at' => $m->created_at?->toISOString(),
            ]);

        // 6. Recent Orders Stream
        $recentOrders = [];
        $latestPos = POSTransaction::orderBy('created_at', 'desc')->limit(3)->get();
        foreach ($latestPos as $t) {
            $recentOrders[] = [
                'id' => $t->id,
                'number' => $t->receipt_number ?? '#POS-' . substr((string)$t->id, 0, 6),
                'channel' => 'POS Terminal',
                'customer' => 'Walk-in Customer',
                'total_cents' => $t->total_cents,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ];
        }
        $latestEcom = EcommerceOrder::orderBy('created_at', 'desc')->limit(3)->get();
        foreach ($latestEcom as $e) {
            $recentOrders[] = [
                'id' => $e->id,
                'number' => $e->order_number,
                'channel' => 'Online Store',
                'customer' => $e->customer_name,
                'total_cents' => $e->total_cents,
                'status' => $e->payment_status,
                'created_at' => $e->created_at?->toISOString(),
            ];
        }

        usort($recentOrders, fn ($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        $recentOrders = array_slice($recentOrders, 0, 5);

        return $this->successResponse([
            'financial' => [
                'monthly_revenue_cents' => $totalRevenueThisMonthCents,
                'prev_monthly_revenue_cents' => $totalRevenuePrevMonthCents,
                'revenue_growth_pct' => $growthPct,
                'today_revenue_cents' => $todayRevenueCents,
                'today_orders_count' => $todayOrdersCount,
                'monthly_trend' => $monthlyTrend,
            ],
            'inventory' => [
                'total_products' => $totalProducts,
                'total_valuation_cents' => $totalValuationCents,
                'optimal_count' => $optimalStockCount,
                'low_stock_count' => count($lowStockItems),
                'out_of_stock_count' => $outOfStockCount,
                'low_stock_items' => array_slice($lowStockItems, 0, 5),
                'recent_movements' => $recentMovements,
            ],
            'operations' => [
                'employee_count' => $employeeCount,
                'pending_leaves_count' => $pendingLeavesCount,
                'open_tickets_count' => $openTicketsCount,
                'active_projects_count' => $activeProjectsCount,
                'open_pos_sessions' => $openPosSessionsCount,
                'active_storefronts' => $activeStorefrontsCount,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}
