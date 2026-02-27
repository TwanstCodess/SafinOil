<?php
// app/Models/Salary.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Employee;
use App\Models\Cash;
use Illuminate\Support\Facades\Log;

class Salary extends Model
{
    protected $table = 'salaries';

    protected $fillable = [
        'employee_id',
        'amount',
        'deductions',
        'net_amount', // **دڵنیابە لە بوونی ئەمە**
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
        'net_amount' => 0, // **دیفۆڵت بۆ net_amount**
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

            } catch (\Exception $e) {
                Log::error('Error in Salary created event: ' . $e->getMessage());
            }
        });
    }
}
