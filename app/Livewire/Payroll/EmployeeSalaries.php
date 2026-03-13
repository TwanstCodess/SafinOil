<?php
namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\Salary;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeSalaries extends Component
{
    use WithPagination;

    public $employeeId;
    public $search = '';
    public $status = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $selectedEmployee = null;
    public $showEmployeeModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount($employeeId = null)
    {
        $this->employeeId = $employeeId;
        if ($employeeId) {
            $this->selectedEmployee = Employee::find($employeeId);
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function viewEmployee($id)
    {
        $this->selectedEmployee = Employee::with(['salaries' => function($q) {
            $q->latest()->take(12);
        }])->find($id);

        $this->showEmployeeModal = true;
    }

    public function getStatsProperty()
    {
        $query = Employee::query();

        if ($this->employeeId) {
            $query->where('id', $this->employeeId);
        }

        return [
            'total_employees' => Employee::where('is_active', true)->count(),
            'total_salaries_this_month' => Salary::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('net_amount'),
            'total_salaries_this_year' => Salary::whereYear('payment_date', now()->year)
                ->sum('net_amount'),
            'average_salary' => Salary::whereYear('payment_date', now()->year)
                ->avg('net_amount') ?? 0,
        ];
    }

    public function render()
    {
        $employees = Employee::query()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('position', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function($query) {
                $query->where('is_active', $this->status === 'active');
            })
            ->with(['salaries' => function($q) {
                $q->latest()->take(1);
            }])
            ->withSum('salaries', 'net_amount')
            ->withCount('salaries')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $cards = [
            [
                'title' => 'کۆی گشتی کارمەندان',
                'value' => $this->stats['total_employees'],
                'icon' => 'users',
                'color' => 'blue',
                'change' => '+12%',
                'period' => 'بەراورد بە مانگی ڕابردوو'
            ],
            [
                'title' => 'مووچەی ئەم مانگە',
                'value' => number_format($this->stats['total_salaries_this_month']) . ' د.ع',
                'icon' => 'currency-dollar',
                'color' => 'green',
                'change' => number_format($this->stats['total_salaries_this_month'] / 1000000, 1) . 'M',
                'period' => 'کۆی گشتی'
            ],
            [
                'title' => 'مووچەی ئەمساڵ',
                'value' => number_format($this->stats['total_salaries_this_year']) . ' د.ع',
                'icon' => 'chart-bar',
                'color' => 'purple',
                'change' => number_format($this->stats['total_salaries_this_year'] / 1000000, 1) . 'M',
                'period' => 'کۆی ساڵانە'
            ],
            [
                'title' => 'تێکڕای مووچە',
                'value' => number_format($this->stats['average_salary']) . ' د.ع',
                'icon' => 'trending-up',
                'color' => 'indigo',
                'change' => '٪' . rand(1, 10),
                'period' => 'تێکڕای کارمەندان'
            ]
        ];

        return view('livewire.payroll.employee-salaries', [
            'employees' => $employees,
            'cards' => $cards
        ]);
    }
}
