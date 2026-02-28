<?php
// app/Models/Salary.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Employee;
use App\Models\Cash;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class Salary extends Model
{
    protected $table = 'salaries';

    protected $fillable = [
        'employee_id',
        'amount',
        'deductions',
        'net_amount',
        'payment_date',
        'month',
        'year',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected $attributes = [
        'deductions' => 0,
        'net_amount' => 0,
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
        static::creating(function ($salary) {
            // دڵنیابە لەوەی net_amount حساب کراوە
            if (!$salary->net_amount && $salary->amount) {
                $salary->net_amount = $salary->amount - ($salary->deductions ?? 0);
            }
        });

        static::created(function ($salary) {
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

                $cash->addExpense($salary->net_amount);

                // تۆمارکردنی مامەڵە لە خشتەی transactions
                $monthNames = [
                    '1' => 'ڕێبەندان', '2' => 'ڕەشەمە', '3' => 'نەورۆز',
                    '4' => 'گوڵان', '5' => 'جۆزەردان', '6' => 'پووشپەڕ',
                    '7' => 'گەلاوێژ', '8' => 'خەرمانان', '9' => 'ڕەزبەر',
                    '10' => 'گەڵاڕێزان', '11' => 'سەرماوەز', '12' => 'بەفرانبار',
                ];

                $monthName = $monthNames[$salary->month] ?? 'مانگ ' . $salary->month;

                Transaction::recordTransaction([
                    'type' => 'salary',
                    'amount' => $salary->net_amount,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $salary->id,
                    'reference_number' => $salary->employee_id,
                    'description' => 'مووچەی ' . ($salary->employee->name ?? 'کارمەند') . ' بۆ مانگی ' . $monthName . 'ی ' . $salary->year . ($salary->notes ? ' - ' . $salary->notes : ''),
                    'transaction_date' => $salary->payment_date,
                    'is_income' => false,
                ]);

            } catch (\Exception $e) {
                Log::error('Error in Salary created event: ' . $e->getMessage());
            }
        });
    }
}
