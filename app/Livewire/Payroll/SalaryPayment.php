<?php
namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\Salary;
use App\Models\Cash;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class SalaryPayment extends Component
{
    use WithPagination;

    public $employee_id;
    public $amount;
    public $deductions = 0;
    public $additions = 0;
    public $net_amount;
    public $month;
    public $year;
    public $payment_date;
    public $notes;
    public $selectedMonth;
    public $selectedYear;
    public $showPaymentModal = false;
    public $selectedEmployee = null;

    protected $rules = [
        'employee_id' => 'required|exists:employees,id',
        'amount' => 'required|numeric|min:0',
        'deductions' => 'numeric|min:0',
        'additions' => 'numeric|min:0',
        'month' => 'required|integer|between:1,12',
        'year' => 'required|integer|min:2020',
        'payment_date' => 'required|date',
        'notes' => 'nullable|string|max:500'
    ];

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
        $this->payment_date = now()->format('Y-m-d');
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    public function updatedEmployeeId()
    {
        if ($this->employee_id) {
            $employee = Employee::find($this->employee_id);
            if ($employee) {
                $this->amount = $employee->base_salary ?? $employee->salary;
                $this->calculateNetAmount();
            }
        }
    }

    public function updatedDeductions()
    {
        $this->calculateNetAmount();
    }

    public function updatedAdditions()
    {
        $this->calculateNetAmount();
    }

    public function calculateNetAmount()
    {
        $this->net_amount = ($this->amount ?? 0) + ($this->additions ?? 0) - ($this->deductions ?? 0);
    }

    public function openPaymentModal($employeeId = null)
    {
        if ($employeeId) {
            $this->selectedEmployee = Employee::find($employeeId);
            $this->employee_id = $employeeId;
            $this->amount = $this->selectedEmployee->base_salary ?? $this->selectedEmployee->salary;
        }

        $this->calculateNetAmount();
        $this->showPaymentModal = true;
    }

    public function savePayment()
    {
        $this->validate();

        // بەدیاریکراوی دووبارە نەبێتەوە بۆ هەمان مانگ و ساڵ
        $exists = Salary::where('employee_id', $this->employee_id)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->exists();

        if ($exists) {
            $this->addError('month', 'ئەم کارمەندە مووچەی ئەم مانگەی وەرگرتووە!');
            return;
        }

        DB::beginTransaction();

        try {
            // دروستکردنی مووچە
            $salary = Salary::create([
                'employee_id' => $this->employee_id,
                'base_amount' => $this->amount,
                'deductions' => $this->deductions,
                'additions' => $this->additions,
                'net_amount' => $this->net_amount,
                'month' => $this->month,
                'year' => $this->year,
                'payment_date' => $this->payment_date,
                'notes' => $this->notes
            ]);

            // کەمکردنەوە لە قاسە (بە Event)
            $cash = Cash::firstOrCreate(
                ['id' => 1],
                ['balance' => 0, 'total_income' => 0, 'total_expense' => 0, 'last_update' => now()]
            );

            $cash->decrement('balance', $this->net_amount);
            $cash->increment('total_expense', $this->net_amount);
            $cash->update(['last_update' => now()]);

            DB::commit();

            session()->flash('message', 'مووچە بە سەرکەوتوویی تۆمار کرا');

            $this->reset(['employee_id', 'amount', 'deductions', 'additions', 'notes']);
            $this->showPaymentModal = false;

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'هەڵە ڕوویدا: ' . $e->getMessage());
        }
    }

    public function getPendingEmployeesProperty()
    {
        return Employee::where('is_active', true)
            ->whereDoesntHave('salaries', function($q) {
                $q->where('month', $this->selectedMonth)
                  ->where('year', $this->selectedYear);
            })
            ->with(['salaries' => function($q) {
                $q->latest()->take(1);
            }])
            ->get();
    }

    public function getPaidEmployeesProperty()
    {
        return Employee::whereHas('salaries', function($q) {
            $q->where('month', $this->selectedMonth)
              ->where('year', $this->selectedYear);
        })
        ->with(['salaries' => function($q) {
            $q->where('month', $this->selectedMonth)
              ->where('year', $this->selectedYear);
        }])
        ->get();
    }

    public function render()
    {
        $employees = Employee::where('is_active', true)->get();

        $monthNames = [
            1 => 'ڕێبەندان', 2 => 'ڕەشەمە', 3 => 'نەورۆز',
            4 => 'گوڵان', 5 => 'جۆزەردان', 6 => 'پووشپەڕ',
            7 => 'گەلاوێژ', 8 => 'خەرمانان', 9 => 'ڕەزبەر',
            10 => 'گەڵاڕێزان', 11 => 'سەرماوەز', 12 => 'بەفرانبار'
        ];

        return view('livewire.payroll.salary-payment', [
            'employees' => $employees,
            'monthNames' => $monthNames,
            'pendingEmployees' => $this->pendingEmployees,
            'paidEmployees' => $this->paidEmployees
        ]);
    }
}
