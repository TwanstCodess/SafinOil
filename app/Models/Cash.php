<?php
// app/Models/Cash.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    protected $table = 'cash';

    protected $fillable = [
        'balance',
        'total_income',
        'total_expense',
        'last_update'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'last_update' => 'date',
    ];

    /**
     * زیادکردنی پارە بۆ قاسە (فرۆشتن)
     */
    public function addIncome($amount)
    {
        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();

        return $this;
    }

    /**
     * کەمکردنەوەی پارە لە قاسە (کڕین و خەرجی)
     */
    public function addExpense($amount)
    {
        $this->balance -= $amount;
        $this->total_expense += $amount;
        $this->last_update = now();
        $this->save();

        return $this;
    }

    /**
     * دەستپێکردنی قاسە بە بڕی دیاریکراو
     */
    public static function initialize($initialBalance = 0)
    {
        return self::create([
            'balance' => $initialBalance,
            'total_income' => 0,
            'total_expense' => 0,
            'last_update' => now(),
        ]);
    }
}
