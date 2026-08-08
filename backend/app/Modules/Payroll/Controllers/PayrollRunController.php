<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\HR\Models\Employee;
use App\Modules\Payroll\Models\PayrollRun;
use App\Modules\Payroll\Models\Payslip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollRunController extends BaseController
{
    private const DEFAULT_GROSS_CENTS = 100000;

    public function index(): JsonResponse
    {
        $runs = PayrollRun::query()
            ->withCount('payslips')
            ->orderByDesc('period_end')
            ->get();

        return $this->successResponse($runs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $run = PayrollRun::create([
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => 'draft',
        ]);

        return $this->createdResponse($run);
    }

    public function process(string $id): JsonResponse
    {
        $run = PayrollRun::findOrFail($id);

        if ($run->status === 'processed') {
            return $this->errorResponse('Payroll run has already been processed.', 422);
        }

        $run = DB::transaction(function () use ($run) {
            $employees = Employee::query()
                ->with('position')
                ->where('status', 'active')
                ->get();

            foreach ($employees as $employee) {
                $gross = $this->resolveGrossCents($employee);
                $deductions = 0;
                $net = $gross - $deductions;

                Payslip::updateOrCreate(
                    [
                        'payroll_run_id' => $run->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'gross_cents' => $gross,
                        'deductions_cents' => $deductions,
                        'net_cents' => $net,
                    ]
                );
            }

            $run->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return $run->fresh(['payslips.employee']);
        });

        return $this->successResponse($run);
    }

    public function payslips(string $id): JsonResponse
    {
        $run = PayrollRun::findOrFail($id);

        $payslips = Payslip::query()
            ->with('employee')
            ->where('payroll_run_id', $run->id)
            ->orderBy('created_at')
            ->get();

        return $this->successResponse($payslips);
    }

    private function resolveGrossCents(Employee $employee): int
    {
        // Employee has no salary field; fall back to position mid/min salary, else default.
        $position = $employee->position;
        if ($position) {
            if (! empty($position->min_salary_cents) && ! empty($position->max_salary_cents)) {
                return (int) round(((int) $position->min_salary_cents + (int) $position->max_salary_cents) / 2);
            }
            if (! empty($position->min_salary_cents)) {
                return (int) $position->min_salary_cents;
            }
            if (! empty($position->max_salary_cents)) {
                return (int) $position->max_salary_cents;
            }
        }

        return self::DEFAULT_GROSS_CENTS;
    }
}
