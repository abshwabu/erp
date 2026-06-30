<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ReportingService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function trialBalance(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        $report = $this->reportingService.trialBalance(
            $request->from_date,
            $request->to_date
        );

        return response()->json($report);
    }

    public function profitLoss(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        $report = $this->reportingService.profitAndLoss(
            $request->from_date,
            $request->to_date
        );

        return response()->json($report);
    }

    public function balanceSheet(Request $request)
    {
        $request->validate([
            'as_of_date' => 'required|date',
        ]);

        $report = $this->reportingService.balanceSheet($request->as_of_date);

        return response()->json($report);
    }

    public function generalLedger(Request $request)
    {
        $request->validate([
            'account_id' => 'required|uuid|exists:accounting_accounts,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        $report = $this->reportingService.generalLedger(
            $request->account_id,
            $request->from_date,
            $request->to_date
        );

        return response()->json($report);
    }

    public function arAging()
    {
        return response()->json($this->reportingService.arAging());
    }

    public function apAging()
    {
        return response()->json($this->reportingService.apAging());
    }
}
