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
        'is_income',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
        'is_income' => 'boolean',
    ];

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'کڕین',
            'sale' => 'فرۆشتن',
            'expense' => 'خەرجی',
            'income' => 'داهات',
            'salary' => 'مووچە',
            'salary_refund' => 'گەڕاندنەوەی مووچە',
            'penalty' => 'سزا',
            'capital_add' => 'زیادکردنی سەرمایە',
            'capital_withdraw' => 'کەمکردنەوەی سەرمایە',
            'credit_payment' => 'دانەوەی قەرز',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'warning',
            'sale' => 'success',
            'expense' => 'danger',
            'income' => 'success',
            'salary' => 'info',
            'salary_refund' => 'success',
            'penalty' => 'danger',
            'capital_add' => 'success',
            'capital_withdraw' => 'danger',
            'credit_payment' => 'success',
            default => 'gray',
        };
    }

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
}
