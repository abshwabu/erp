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

        if (!$entitlement) {
            return false;
        }

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

    /**
     * Accrue 1/12 of each leave type's annual allowance for active employees.
     */
    public function accrueMonthlyLeave(): void
    {
        $year = Carbon::now()->year;
        $types = LeaveType::query()->get();
        $employees = Employee::query()->where('status', 'active')->get();

        foreach ($employees as $employee) {
            foreach ($types as $type) {
                $annual = (float) ($type->max_days_per_year ?? 0);
                if ($annual <= 0) {
                    continue;
                }

                $monthly = round($annual / 12, 2);

                $entitlement = LeaveEntitlement::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ],
                    [
                        'entitled_days' => $annual,
                        'accrued_days' => 0,
                        'taken_days' => 0,
                        'carried_over_days' => 0,
                    ]
                );

                $entitlement->accrued_days = (float) $entitlement->accrued_days + $monthly;
                $entitlement->save();
            }
        }
    }

    /**
     * Carry over unused days up to 5 into the next year.
     */
    public function carryOverYearEnd(): void
    {
        $fromYear = Carbon::now()->year - 1;
        $toYear = Carbon::now()->year;
        $maxCarry = 5.0;

        $entitlements = LeaveEntitlement::query()->where('year', $fromYear)->get();

        foreach ($entitlements as $entitlement) {
            $unused = max(0, (float) $entitlement->accrued_days - (float) $entitlement->taken_days);
            $carry = min($unused, $maxCarry);

            if ($carry <= 0) {
                continue;
            }

            $next = LeaveEntitlement::firstOrCreate(
                [
                    'employee_id' => $entitlement->employee_id,
                    'leave_type_id' => $entitlement->leave_type_id,
                    'year' => $toYear,
                ],
                [
                    'entitled_days' => (float) ($entitlement->entitled_days ?? 0),
                    'accrued_days' => 0,
                    'taken_days' => 0,
                    'carried_over_days' => 0,
                ]
            );

            $next->carried_over_days = (float) $next->carried_over_days + $carry;
            $next->accrued_days = (float) $next->accrued_days + $carry;
            $next->save();
        }
    }
}
