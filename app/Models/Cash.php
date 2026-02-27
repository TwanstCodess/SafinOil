<?php
// app/Models/Cash.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    protected $table = 'cash';

    protected $fillable = [
        'balance', 'total_income', 'total_expense', 'last_update'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
    ];

    public function addIncome($amount)
    {
        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();
    }

    public function addExpense($amount)
    {
        $this->balance -= $amount;
        $this->total_expense += $amount;
        $this->last_update = now();
        $this->save();
    }
}
