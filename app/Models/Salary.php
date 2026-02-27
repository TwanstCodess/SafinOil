<?php
// app/Models/Salary.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    protected $fillable = [
        'employee_id', 'amount', 'deductions', 'net_amount',
        'payment_date', 'month', 'year', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function booted()
    {
        static::created(function ($salary) {
            // کەمکردنەوەی پارە لە قاسە
            $cash = Cash::first();
            if ($cash) {
                $cash->addExpense($salary->net_amount);
            }
        });
    }
}
