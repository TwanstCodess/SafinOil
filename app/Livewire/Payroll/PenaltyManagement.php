<?php
namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\Penalty;
use Livewire\Component;
use Livewire\WithPagination;

class PenaltyManagement extends Component
{
    use WithPagination;

    public $employee_id;
    public $amount;
    public $penalty_date;
    public $reason;
    public $notes;
    public $showModal = false;
    public $editingPenalty = null;

    protected $rules = [
        'employee_id' => 'required|exists:employees,id',
        'amount' => 'required|numeric|min:1',
        'penalty_date' => 'required|date',
        'reason' => 'required|string|max:255',
        'notes' => 'nullable|string|max:500'
    ];

    public function mount()
    {
        $this->penalty_date = now()->format('Y-m-d');
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['employee_id', 'amount', 'reason', 'notes', 'editingPenalty']);
        $this->penalty_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function editPenalty($id)
    {
        $this->editingPenalty = Penalty::find($id);
        $this->employee_id = $this->editingPenalty->employee_id;
        $this->amount = $this->editingPenalty->amount;
        $this->penalty_date = $this->editingPenalty->penalty_date->format('Y-m-d');
        $this->reason = $this->editingPenalty->reason;
        $this->notes = $this->editingPenalty->notes;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingPenalty) {
            $this->editingPenalty->update([
                'employee_id' => $this->employee_id,
                'amount' => $this->amount,
                'penalty_date' => $this->penalty_date,
                'reason' => $this->reason,
                'notes' => $this->notes
            ]);
            $message = 'سزا بە سەرکەوتوویی نوێ کرایەوە';
        } else {
            Penalty::create([
                'employee_id' => $this->employee_id,
                'amount' => $this->amount,
                'penalty_date' => $this->penalty_date,
                'reason' => $this->reason,
                'notes' => $this->notes
            ]);
            $message = 'سزا بە سەرکەوتوویی تۆمار کرا';
        }

        session()->flash('message', $message);
        $this->showModal = false;
        $this->reset(['employee_id', 'amount', 'reason', 'notes', 'editingPenalty']);
    }

    public function deletePenalty($id)
    {
        Penalty::find($id)->delete();
        session()->flash('message', 'سزا بە سەرکەوتوویی سڕایەوە');
    }

    public function render()
    {
        $penalties = Penalty::with('employee')
            ->latest()
            ->paginate(15);

        $employees = Employee::where('is_active', true)->get();

        $stats = [
            'total_this_month' => Penalty::whereMonth('penalty_date', now()->month)
                ->whereYear('penalty_date', now()->year)
                ->sum('amount'),
            'total_this_year' => Penalty::whereYear('penalty_date', now()->year)
                ->sum('amount'),
            'count_this_month' => Penalty::whereMonth('penalty_date', now()->month)
                ->whereYear('penalty_date', now()->year)
                ->count(),
            'most_penalized' => Employee::withSum('penalties', 'amount')
                ->where('is_active', true)
                ->orderByDesc('penalties_sum_amount')
                ->first()
        ];

        return view('livewire.payroll.penalty-management', [
            'penalties' => $penalties,
            'employees' => $employees,
            'stats' => $stats
        ]);
    }
}
