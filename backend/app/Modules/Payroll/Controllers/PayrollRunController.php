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
    private const DEFAULT_GROSS_CENTS = 100000; // 1,000.00 default fallback

    public function index(): JsonResponse
    {
        $runs = PayrollRun::query()
            ->withCount('payslips')
            ->withSum('payslips', 'net_cents')
            ->withSum('payslips', 'gross_cents')
            ->withSum('payslips', 'deductions_cents')
            ->orderByDesc('period_end')
            ->get();

        return $this->successResponse($runs);
    }

    public function preview(): JsonResponse
    {
        $employees = Employee::query()
            ->with(['department', 'position'])
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $previewData = $employees->map(function ($employee) {
            $grossCents = $this->resolveGrossCents($employee);
            $deductionsCents = 0;
            $netCents = $grossCents - $deductionsCents;

            return [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => "{$employee->first_name} {$employee->last_name}",
                'department' => $employee->department?->name ?? 'Unassigned',
                'position' => $employee->position?->title ?? 'Unassigned',
                'base_salary' => $employee->base_salary,
                'currency' => $employee->salary_currency ?? 'USD',
                'salary_type' => $employee->salary_type ?? 'monthly',
                'payment_method' => $employee->payment_method ?? 'bank_transfer',
                'bank_name' => $employee->bank_name,
                'bank_account_number' => $employee->bank_account_number,
                'gross_cents' => $grossCents,
                'deductions_cents' => $deductionsCents,
                'net_cents' => $netCents,
            ];
        });

        $totalNetCents = $previewData->sum('net_cents');
        $totalGrossCents = $previewData->sum('gross_cents');

        // Dominant currency among employees
        $primaryCurrency = $employees->first()?->salary_currency ?? 'USD';

        return $this->successResponse([
            'employee_count' => $employees->count(),
            'currency' => $primaryCurrency,
            'total_gross_cents' => $totalGrossCents,
            'total_net_cents' => $totalNetCents,
            'employees' => $previewData,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $run = PayrollRun::with([
            'payslips.employee.department',
            'payslips.employee.position',
        ])
        ->withSum('payslips', 'net_cents')
        ->withSum('payslips', 'gross_cents')
        ->withSum('payslips', 'deductions_cents')
        ->findOrFail($id);

        return $this->successResponse($run);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'auto_process' => ['nullable', 'boolean'],
        ]);

        $run = PayrollRun::create([
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => 'draft',
        ]);

        if (!empty($validated['auto_process'])) {
            return $this->process($run->id);
        }

        return $this->createdResponse($run);
    }

    public function process(string $id): JsonResponse
    {
        $run = PayrollRun::findOrFail($id);

        $run = DB::transaction(function () use ($run) {
            $employees = Employee::query()
                ->with(['department', 'position'])
                ->where('status', 'active')
                ->get();

            // Clear old payslips to cleanly recompute with latest employee salaries
            $run->payslips()->delete();

            foreach ($employees as $employee) {
                $gross = $this->resolveGrossCents($employee);
                $deductions = 0;
                $net = $gross - $deductions;

                Payslip::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'gross_cents' => $gross,
                    'deductions_cents' => $deductions,
                    'net_cents' => $net,
                ]);
            }

            $run->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return $run->fresh(['payslips.employee.department', 'payslips.employee.position']);
        });

        return $this->successResponse($run);
    }

    public function destroy(string $id): JsonResponse
    {
        $run = PayrollRun::findOrFail($id);

        $run->payslips()->delete();
        $run->delete();

        return $this->successResponse(null, 'Payroll run deleted successfully.');
    }

    public function payslips(string $id): JsonResponse
    {
        $run = PayrollRun::findOrFail($id);

        $payslips = Payslip::query()
            ->with(['employee.department', 'employee.position'])
            ->where('payroll_run_id', $run->id)
            ->orderBy('created_at')
            ->get();

        return $this->successResponse($payslips);
    }

    private function resolveGrossCents(Employee $employee): int
    {
        // 1. Use employee's configured base salary if present
        if (!empty($employee->base_salary) && (float) $employee->base_salary > 0) {
            return (int) round((float) $employee->base_salary * 100);
        }

        // 2. Fall back to position min/max salary range
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
