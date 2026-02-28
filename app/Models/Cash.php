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
        'last_update',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'last_update' => 'date',
    ];

    /**
     * زیادکردنی پارە بۆ قاسە
     */
    public function addMoney($amount, $description = null)
    {
        $this->balance += $amount;
        $this->total_income += $amount;
        $this->last_update = now();
        $this->save();

        // ئارادی: تۆمارکردنی مامەڵەکە
        activity()
            ->performedOn($this)
            ->withProperties([
                'amount' => $amount,
                'type' => 'add',
                'description' => $description,
                'previous_balance' => $this->balance - $amount,
                'new_balance' => $this->balance,
            ])
            ->log('پارە زیاد کرا بۆ قاسە');

        return $this;
    }

    /**
     * کەمکردنەوەی پارە لە قاسە
     */
    public function withdrawMoney($amount, $description = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('پارەی پێویست لە قاسەدا نییە');
        }

        $this->balance -= $amount;
        $this->total_expense += $amount;
        $this->last_update = now();
        $this->save();

        // ئارادی: تۆمارکردنی مامەڵەکە
        activity()
            ->performedOn($this)
            ->withProperties([
                'amount' => $amount,
                'type' => 'withdraw',
                'description' => $description,
                'previous_balance' => $this->balance + $amount,
                'new_balance' => $this->balance,
            ])
            ->log('پارە کەم کرایەوە لە قاسە');

        return $this;
    }

    /**
     * وەرگرتنی ڕەوشتی قاسە بە شێوازی ڕێکخراو
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance).' دینار';
    }
}
