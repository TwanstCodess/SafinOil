<?php
namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\Salary;
use Livewire\Component;
use Livewire\WithPagination;

class SalaryHistory extends Component
{
    use WithPagination;

    public $employee_id = '';
    public $year = '';
    public $month = '';
    public $search = '';
    public $showDetails = false;
    public $selectedSalary = null;
    public $selectedEmployee = null;

    protected $queryString = [
        'employee_id' => ['except' => ''],
        'year' => ['except' => ''],
        'month' => ['except' => ''],
        'search' => ['except' => '']
    ];

    public function mount()
    {
        $this->year = now()->year;
    }

    public function viewDetails($salaryId)
    {
        $this->selectedSalary = Salary::with('employee')->find($salaryId);
        $this->showDetails = true;
    }

    public function getEmployeeSummaryProperty()
    {
        if ($this->selectedSalary) {
            return [
                'total_paid' => Salary::where('employee_id', $this->selectedSalary->employee_id)->sum('net_amount'),
                'total_deductions' => Salary::where('employee_id', $this->selectedSalary->employee_id)->sum('deductions'),
                'total_additions' => Salary::where('employee_id', $this->selectedSalary->employee_id)->sum('additions'),
                'payments_count' => Salary::where('employee_id', $this->selectedSalary->employee_id)->count(),
                'last_payment' => Salary::where('employee_id', $this->selectedSalary->employee_id)
                    ->where('id', '!=', $this->selectedSalary->id)
                    ->latest()
                    ->first()
            ];
        }
        return [];
    }

    public function render()
    {
        $query = Salary::with('employee');

        if ($this->employee_id) {
            $query->where('employee_id', $this->employee_id);
        }

        if ($this->year) {
            $query->where('year', $this->year);
        }

        if ($this->month) {
            $query->where('month', $this->month);
        }

        if ($this->search) {
            $query->whereHas('employee', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        $salaries = $query->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(15);

        $employees = Employee::where('is_active', true)->get();

        $years = Salary::select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        $stats = [
            'total' => $salaries->sum('net_amount'),
            'count' => $salaries->total(),
            'avg' => $salaries->total() > 0 ? $salaries->sum('net_amount') / $salaries->total() : 0,
            'deductions_total' => $salaries->sum('deductions'),
            'additions_total' => $salaries->sum('additions')
        ];

        $monthNames = [
            1 => 'ڕێبەندان', 2 => 'ڕەشەمە', 3 => 'نەورۆز',
            4 => 'گوڵان', 5 => 'جۆزەردان', 6 => 'پووشپەڕ',
            7 => 'گەلاوێژ', 8 => 'خەرمانان', 9 => 'ڕەزبەر',
            10 => 'گەڵاڕێزان', 11 => 'سەرماوەز', 12 => 'بەفرانبار'
        ];

        return view('livewire.payroll.salary-history', [
            'salaries' => $salaries,
            'employees' => $employees,
            'years' => $years,
            'stats' => $stats,
            'monthNames' => $monthNames
        ]);
    }
}
