<?php
// app/Models/Transaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Cash;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'transaction_number',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'transactionable_type',
        'transactionable_id',
        'reference_number',
        'description',
        'transaction_date',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * وەرگێڕانی جۆری مامەڵە بۆ کوردی
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'کڕین',
            'sale' => 'فرۆشتن',
            'expense' => 'خەرجی',
            'salary' => 'مووچە',
            'penalty' => 'سزا',
            'capital_add' => 'زیادکردنی سەرمایە',
            'capital_withdraw' => 'کەمکردنەوەی سەرمایە',
            'cash_add' => 'زیادکردنی پارە',
            'cash_withdraw' => 'کەمکردنەوەی پارە',
            default => $this->type,
        };
    }

    /**
     * وەرگرتنی ڕەنگی جۆری مامەڵە
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'warning',
            'sale' => 'success',
            'expense' => 'danger',
            'salary' => 'info',
            'penalty' => 'danger',
            'capital_add' => 'success',
            'capital_withdraw' => 'danger',
            'cash_add' => 'success',
            'cash_withdraw' => 'danger',
            default => 'gray',
        };
    }

    /**
     * ئایا مامەڵەکە داهاتە؟
     */
    public function getIsIncomeAttribute(): bool
    {
        return in_array($this->type, ['sale', 'cash_add', 'capital_add']);
    }

    /**
     * ئایا مامەڵەکە خەرجیە؟
     */
    public function getIsExpenseAttribute(): bool
    {
        return in_array($this->type, ['purchase', 'expense', 'salary', 'penalty', 'cash_withdraw', 'capital_withdraw']);
    }

    /**
     * ژمارەی مامەڵە دروست بکە
     */
    public static function generateTransactionNumber(): string
    {
        $prefix = 'TRX';
        $year = now()->format('Y');
        $month = now()->format('m');
        $lastTransaction = self::whereYear('created_at', now()->year)
                               ->whereMonth('created_at', now()->month)
                               ->count();

        $number = str_pad($lastTransaction + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$number}";
    }

    /**
     * تۆمارکردنی مامەڵە
     */
    public static function recordTransaction($data)
    {
        // وەرگرتنی قاسە
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

        $balanceBefore = $cash->balance;

        // ئەپدەیت کردنی قاسە
        if ($data['is_income'] ?? false) {
            $cash->balance += $data['amount'];
            $cash->total_income += $data['amount'];
        } else {
            $cash->balance -= $data['amount'];
            $cash->total_expense += $data['amount'];
        }
        $cash->last_update = now();
        $cash->save();

        // دروستکردنی مامەڵە
        return self::create([
            'transaction_number' => self::generateTransactionNumber(),
            'type' => $data['type'],
            'amount' => $data['amount'],
            'balance_before' => $balanceBefore,
            'balance_after' => $cash->balance,
            'transactionable_type' => $data['transactionable_type'] ?? null,
            'transactionable_id' => $data['transactionable_id'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'description' => $data['description'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);
    }
}
