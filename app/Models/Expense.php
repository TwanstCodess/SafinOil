<?php
// app/Models/Expense.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'title', 'amount', 'expense_date', 'category', 'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    protected static function booted()
    {
        static::created(function ($expense) {
            // کەمکردنەوەی پارە لە قاسە
            $cash = Cash::first();
            if ($cash) {
                $cash->addExpense($expense->amount);
            }
        });
    }
}
