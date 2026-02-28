<?php
// app/Models/Penalty.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Cash;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class Penalty extends Model
{
    protected $table = 'penalties';

    protected $fillable = [
        'employee_id', 'amount', 'penalty_date', 'reason', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    protected static function booted()
    {
        static::created(function ($penalty) {
            try {
                // کەمکردنەوەی پارە لە قاسە (سزا دەبێتە خەرجی)
                $cash = Cash::first();
                if (!$cash) {
                    $cash = Cash::create([
                        'balance' => 0,
                        'total_income' => 0,
                        'total_expense' => 0,
                        'last_update' => now(),
                    ]);
                }

                $cash->addExpense($penalty->amount);

                // تۆمارکردنی مامەڵە لە خشتەی transactions
                Transaction::recordTransaction([
                    'type' => 'penalty',
                    'amount' => $penalty->amount,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $penalty->id,
                    'reference_number' => $penalty->employee_id,
                    'description' => 'سزای ' . ($penalty->employee->name ?? 'کارمەند') . ': ' . $penalty->reason . ($penalty->notes ? ' - ' . $penalty->notes : ''),
                    'transaction_date' => $penalty->penalty_date,
                    'is_income' => false, // سزا دەبێتە خەرجی بۆ کۆمپانیا
                ]);

            } catch (\Exception $e) {
                Log::error('Error in Penalty created event: ' . $e->getMessage());
            }
        });
    }
}
