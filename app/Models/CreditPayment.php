<?php
// app/Models/CreditPayment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    protected $table = 'credit_payments';

    protected $fillable = [
        'sale_id', 'customer_id', 'amount',
        'payment_date', 'payment_method', 'reference_number', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            try {
                // ١. نوێکردنەوەی قەرزی کڕیار
                $payment->customer->updateDebt();

                // ٢. نوێکردنەوەی ڕەوشتی فرۆشتن
                $sale = $payment->sale;
                $sale->paid_amount += $payment->amount;
                $sale->remaining_amount -= $payment->amount;

                if ($sale->remaining_amount <= 0) {
                    $sale->status = 'paid';
                    $sale->paid_date = $payment->payment_date;
                } else {
                    $sale->status = 'partial';
                }
                $sale->save();

                // ٣. زیادکردنی پارە بۆ قاسە
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
                $cash->balance += $payment->amount;
                $cash->total_income += $payment->amount;
                $cash->last_update = now();
                $cash->save();

                // ٤. تۆمارکردنی مامەڵە
                Transaction::create([
                    'transaction_number' => Transaction::generateTransactionNumber(),
                    'type' => 'credit_payment',
                    'amount' => $payment->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $cash->balance,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $payment->id,
                    'reference_number' => $payment->sale_id,
                    'description' => 'دانەوەی قەرز لەلایەن ' . ($payment->customer->name ?? 'کڕیار'),
                    'transaction_date' => $payment->payment_date,
                    'created_by' => auth()->user()?->name ?? 'سیستەم',
                ]);

            } catch (\Exception $e) {
                \Log::error('Error in CreditPayment created: ' . $e->getMessage());
            }
        });
    }
}
