<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Salary;
use App\Models\Penalty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index()
    {
        $stats = [
            'total_employees' => Employee::where('is_active', true)->count(),
            'total_salaries' => Salary::whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('net_amount'),
            'total_penalties' => Penalty::whereYear('penalty_date', now()->year)
                ->whereMonth('penalty_date', now()->month)
                ->sum('amount'),
            'pending_payments' => Employee::where('is_active', true)
                ->whereDoesntHave('salaries', function($q) {
                    $q->whereMonth('payment_date', now()->month)
                      ->whereYear('payment_date', now()->year);
                })->count()
        ];

        $recentSalaries = Salary::with('employee')
            ->latest()
            ->take(10)
            ->get();

        $topEmployees = Employee::withSum('salaries', 'net_amount')
            ->withSum('penalties', 'amount')
            ->where('is_active', true)
            ->orderByDesc('salaries_sum_net_amount')
            ->take(5)
            ->get();

        return view('payroll.index', compact('stats', 'recentSalaries', 'topEmployees'));
    }

    public function employeeSalaries($employeeId = null)
    {
        if ($employeeId) {
            $employee = Employee::with(['salaries' => function($q) {
                $q->latest()->take(12);
            }, 'penalties' => function($q) {
                $q->latest()->take(12);
            }])->findOrFail($employeeId);

            $statistics = [
                'total_paid' => $employee->salaries->sum('net_amount'),
                'total_penalties' => $employee->penalties->sum('amount'),
                'average_salary' => $employee->salaries->avg('net_amount'),
                'last_payment' => $employee->salaries->first()?->payment_date,
                'months_paid' => $employee->salaries->count()
            ];

            $monthlyData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $salary = $employee->salaries
                    ->where('month', $month->month)
                    ->where('year', $month->year)
                    ->first();

                $monthlyData[] = [
                    'month' => $month->format('Y-m'),
                    'month_name' => $month->format('F Y'),
                    'salary' => $salary?->net_amount ?? 0,
                    'penalty' => $employee->penalties
                        ->where('penalty_date', '>=', $month->startOfMonth())
                        ->where('penalty_date', '<=', $month->endOfMonth())
                        ->sum('amount')
                ];
            }

            return view('payroll.employee-detail', compact('employee', 'statistics', 'monthlyData'));
        }

        $employees = Employee::with(['salaries' => function($q) {
            $q->latest()->take(1);
        }])->where('is_active', true)
          ->paginate(15);

        return view('payroll.employees', compact('employees'));
    }

    public function salaryReport(Request $request)
    {
        $query = Salary::with('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        $salaries = $query->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(20);

        $summary = [
            'total' => $salaries->sum('net_amount'),
            'avg' => $salaries->avg('net_amount'),
            'count' => $salaries->count(),
            'with_penalty' => $salaries->where('deductions', '>', 0)->count()
        ];

        $employees = Employee::where('is_active', true)->get();

        return view('payroll.salary-report', compact('salaries', 'summary', 'employees'));
    }

    public function penaltyReport(Request $request)
    {
        $query = Penalty::with('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('penalty_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('penalty_date', '<=', $request->to_date);
        }

        $penalties = $query->orderByDesc('penalty_date')->paginate(20);

        $summary = [
            'total' => $penalties->sum('amount'),
            'count' => $penalties->count(),
            'avg' => $penalties->avg('amount'),
            'max' => $penalties->max('amount')
        ];

        $employees = Employee::where('is_active', true)->get();

        return view('payroll.penalty-report', compact('penalties', 'summary', 'employees'));
    }
}
