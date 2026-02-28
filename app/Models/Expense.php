<?php
// app/Models/Expense.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cash;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'title', 'amount', 'expense_date', 'category', 'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    protected static function booted()
    {
        static::created(function ($expense) {
            try {
                // کەمکردنەوەی پارە لە قاسە
                $cash = Cash::first();
                if (!$cash) {
                    $cash = Cash::create([
                        'balance' => 0,
                        'total_income' => 0,
                        'total_expense' => 0,
                        'last_update' => now(),
                    ]);
                }

                $cash->addExpense($expense->amount);

                // تۆمارکردنی مامەڵە لە خشتەی transactions
                Transaction::recordTransaction([
                    'type' => 'expense',
                    'amount' => $expense->amount,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $expense->id,
                    'reference_number' => $expense->id,
                    'description' => $expense->title . ($expense->description ? ' - ' . $expense->description : ''),
                    'transaction_date' => $expense->expense_date,
                    'is_income' => false,
                ]);

            } catch (\Exception $e) {
                Log::error('Error in Expense created event: ' . $e->getMessage());
            }
        });
    }
}
