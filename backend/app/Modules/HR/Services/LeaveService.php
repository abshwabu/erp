<?php

declare(strict_types=1);

namespace App\Modules\HR\Services;

use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\LeaveRequest;
use App\Modules\HR\Models\LeaveEntitlement;
use App\Modules\HR\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveService
{
    public function checkBalance(string $employeeId, string $leaveTypeId, float $days): bool
    {
        $entitlement = LeaveEntitlement::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', Carbon::now()->year)
            ->first();

        if (!$entitlement) return false;

        $availableDays = $entitlement->accrued_days - $entitlement->taken_days;
        return $availableDays >= $days;
    }

    public function calculateWorkingDays(string $startDate, string $endDate): float
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekday()) {
                $days++;
            }
        }
        
        return (float) $days;
    }

    public function accrueMonthlyLeave(): void
    {
        // TODO: Logic to accrue leave based on policies
    }

    public function carryOverYearEnd(): void
    {
        // TODO: Logic to carry over leave
    }
}
