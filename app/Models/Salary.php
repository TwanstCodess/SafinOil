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
            // حسابکردنی مووچەی پاک (net_amount)
            if (!$salary->net_amount && $salary->amount) {
                $salary->net_amount = $salary->amount - ($salary->deductions ?? 0);
            }
        });

        // **چاککردن: تەنها یەک جار پارە کەم بکەرەوە**
        static::created(function ($salary) {
            try {
                // دڵنیابوون لە بوونی قاسە
                $cash = Cash::first();
                if (!$cash) {
                    $cash = Cash::create([
                        'balance' => 0,
                        'total_income' => 0,
                        'total_expense' => 0,
                        'capital' => 0,
                        'profit' => 0,
                        'last_update' => now(),
                    ]);
                }

                // **بەکارهێنانی net_amount نەک amount (بۆ ڕەچاوکردنی سزا)**
                $amountToDeduct = $salary->net_amount; // ئەمە مووچەی پاکە (مووچە - سزا)

                // **تەنها یەک جار: کەمکردنەوەی پارە لە قاسە**
                $balanceBefore = $cash->balance;
                $cash->balance -= $amountToDeduct;
                $cash->total_expense += $amountToDeduct;
                $cash->last_update = now();
                $cash->save();

                // ناوی مانگ بۆ وەسف
                $monthNames = [
                    '1' => 'ڕێبەندان', '2' => 'ڕەشەمە', '3' => 'نەورۆز',
                    '4' => 'گوڵان', '5' => 'جۆزەردان', '6' => 'پووشپەڕ',
                    '7' => 'گەلاوێژ', '8' => 'خەرمانان', '9' => 'ڕەزبەر',
                    '10' => 'گەڵاڕێزان', '11' => 'سەرماوەز', '12' => 'بەفرانبار',
                ];
                $monthName = $monthNames[$salary->month] ?? 'مانگ ' . $salary->month;

                // **دروستکردنی transaction تەنها بۆ تۆمار، بەبێ گۆڕینی قاسە**
                Transaction::create([
                    'transaction_number' => Transaction::generateTransactionNumber(),
                    'type' => 'salary',
                    'amount' => $amountToDeduct,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $cash->balance,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $salary->id,
                    'reference_number' => (string) $salary->employee_id,
                    'description' => 'مووچەی ' . ($salary->employee->name ?? 'کارمەند') .
                                     ' بۆ مانگی ' . $monthName . 'ی ' . $salary->year .
                                     ($salary->deductions > 0 ? ' (سزا: ' . number_format($salary->deductions) . ' دینار)' : '') .
                                     ($salary->notes ? ' - ' . $salary->notes : ''),
                    'transaction_date' => $salary->payment_date,
                    'is_income' => false,
                    'created_by' => auth()->user()?->name ?? 'سیستەم',
                ]);

            } catch (\Exception $e) {
                Log::error('Error in Salary created event: ' . $e->getMessage());
            }
        });

        // **کاتێک مووچە دەسڕێتەوە، پارە بگەڕێنەرەوە قاسە**
        static::deleted(function ($salary) {
            try {
                $cash = Cash::first();
                if ($cash) {
                    // گەڕاندنەوەی پارە بۆ قاسە
                    $amountToAdd = $salary->net_amount;

                    $balanceBefore = $cash->balance;
                    $cash->balance += $amountToAdd;
                    $cash->total_income += $amountToAdd; // وەک داهات تۆمار دەکرێت کاتێک دەسڕێتەوە
                    $cash->last_update = now();
                    $cash->save();

                    // تۆمارکردنی transaction بۆ سڕینەوە
                    Transaction::create([
                        'transaction_number' => Transaction::generateTransactionNumber(),
                        'type' => 'salary_refund',
                        'amount' => $amountToAdd,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $cash->balance,
                        'transactionable_type' => self::class,
                        'transactionable_id' => $salary->id,
                        'reference_number' => (string) $salary->employee_id,
                        'description' => 'گەڕاندنەوەی مووچە - سڕینەوە',
                        'transaction_date' => now(),
                        'is_income' => true,
                        'created_by' => auth()->user()?->name ?? 'سیستەم',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error in Salary deleted event: ' . $e->getMessage());
            }
        });
    }
}
