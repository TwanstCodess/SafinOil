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
        'capital',
        'profit',
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
     * زیادکردنی سەرمایە - بەبێ تۆمارکردنی دووبارە لە Transaction
     */
    public function addCapital($amount, $description = null, $date = null)
    {
        $balanceBefore = $this->balance;

        $this->capital += $amount;
        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();

        // تۆمارکردنی مامەڵە - تەنها بۆ ڕاپۆرت، قاسە ناگۆڕێتەوە
        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'capital_add',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'زیادکردنی سەرمایە',
            'transaction_date' => $date ?? now(),
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);

        return $this;
    }

    /**
     * کەمکردنەوەی سەرمایە - بەبێ تۆمارکردنی دووبارە
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

        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'capital_withdraw',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'کەمکردنەوەی سەرمایە',
            'transaction_date' => $date ?? now(),
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);

        return $this;
    }

    /**
     * زیادکردنی پارە بۆ قاسە (داهات) - بەبێ تۆمارکردنی دووبارە
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
     * زیادکردنی خەرجی (کەمکردنەوەی پارە لە قاسە) - بەبێ تۆمارکردنی دووبارە
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
     * زیادکردنی پارە بۆ قاسە - بەبێ تۆمارکردنی دووبارە
     */
    public function addMoney($amount, $description = null, $date = null)
    {
        $balanceBefore = $this->balance;

        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();

        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'cash_add',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'زیادکردنی پارە بۆ قاسە',
            'transaction_date' => $date ?? now(),
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);

        return $this;
    }

    /**
     * کەمکردنەوەی پارە لە قاسە - بەبێ تۆمارکردنی دووبارە
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

        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'cash_withdraw',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'کەمکردنەوەی پارە لە قاسە',
            'transaction_date' => $date ?? now(),
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);

        return $this;
    }

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
