<?php
// app/Models/Cash.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class Cash extends Model
{
    protected $table = 'cash';

    protected $fillable = [
        'balance',
        'total_income',
        'total_expense',
        'capital', // سەرمایە
        'profit',  // قازانج
        'last_update',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'capital' => 'decimal:2',
        'profit' => 'decimal:2',
        'last_update' => 'date',
    ];

    /**
     * حسابکردنی قازانج
     */
    public function calculateProfit()
    {
        $this->profit = $this->total_income - $this->total_expense;
        $this->save();
        return $this->profit;
    }

    /**
     * زیادکردنی سەرمایە
     */
    public function addCapital($amount, $description = null, $date = null)
    {
        $balanceBefore = $this->balance;

        $this->capital += $amount;
        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();

        // تۆمارکردنی مامەڵە
        Transaction::recordTransaction([
            'type' => 'capital_add',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'زیادکردنی سەرمایە',
            'transaction_date' => $date ?? now(),
            'is_income' => true,
            'is_capital' => true,
        ]);

        return $this;
    }

    /**
     * کەمکردنەوەی سەرمایە (وەرگرتن)
     */
    public function withdrawCapital($amount, $description = null, $date = null)
    {
        if ($this->capital < $amount) {
            throw new \Exception('سەرمایەی پێویست بوونی نییە');
        }

        $balanceBefore = $this->balance;

        $this->capital -= $amount;
        $this->balance -= $amount;
        $this->total_expense += $amount;
        $this->last_update = now();
        $this->save();

        // تۆمارکردنی مامەڵە
        Transaction::recordTransaction([
            'type' => 'capital_withdraw',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'کەمکردنەوەی سەرمایە',
            'transaction_date' => $date ?? now(),
            'is_income' => false,
            'is_capital' => true,
        ]);

        return $this;
    }

    /**
     * زیادکردنی پارە بۆ قاسە (داهات)
     */
    public function addIncome($amount, $description = null)
    {
        $balanceBefore = $this->balance;

        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();

        return $this;
    }

    /**
     * زیادکردنی خەرجی (کەمکردنەوەی پارە لە قاسە)
     */
    public function addExpense($amount, $description = null)
    {
        $balanceBefore = $this->balance;

        $this->balance -= $amount;
        $this->total_expense += $amount;
        $this->last_update = now();
        $this->save();

        return $this;
    }

    /**
     * زیادکردنی پارە بۆ قاسە (بۆ مامەڵە دەستییەکان)
     */
    public function addMoney($amount, $description = null, $date = null)
    {
        $balanceBefore = $this->balance;

        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();

        // تۆمارکردنی مامەڵە
        Transaction::recordTransaction([
            'type' => 'cash_add',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'زیادکردنی پارە بۆ قاسە',
            'transaction_date' => $date ?? now(),
            'is_income' => true,
        ]);

        return $this;
    }

    /**
     * کەمکردنەوەی پارە لە قاسە (بۆ مامەڵە دەستییەکان)
     */
    public function withdrawMoney($amount, $description = null, $date = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('پارەی پێویست لە قاسەدا نییە');
        }

        $balanceBefore = $this->balance;

        $this->balance -= $amount;
        $this->total_expense += $amount;
        $this->last_update = now();
        $this->save();

        // تۆمارکردنی مامەڵە
        Transaction::recordTransaction([
            'type' => 'cash_withdraw',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'کەمکردنەوەی پارە لە قاسە',
            'transaction_date' => $date ?? now(),
            'is_income' => false,
        ]);

        return $this;
    }

    /**
     * وەرگرتنی ڕەوشتی قاسە بە شێوازی ڕێکخراو
     */
    public function getFormattedBalanceAttribute()
    {
        return $this->formatMoney($this->balance);
    }

    public function getFormattedCapitalAttribute()
    {
        return $this->formatMoney($this->capital);
    }

    public function getFormattedProfitAttribute()
    {
        return $this->formatMoney($this->profit);
    }

    private function formatMoney($amount)
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 2) . ' ملیۆن دینار';
        } elseif ($amount >= 1000) {
            return number_format($amount / 1000, 2) . ' هەزار دینار';
        }
        return number_format($amount) . ' دینار';
    }

    /**
     * دەستپێکردنی قاسە بە بڕی دیاریکراو
     */
    public static function initialize($initialBalance = 0, $initialCapital = 0)
    {
        return self::create([
            'balance' => $initialBalance,
            'capital' => $initialCapital,
            'profit' => 0,
            'total_income' => 0,
            'total_expense' => 0,
            'last_update' => now(),
        ]);
    }
}
