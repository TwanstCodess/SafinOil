<?php
// app/Models/CreditPayment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    protected $table = 'credit_payments';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'transaction_number'
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
        static::creating(function ($payment) {
            // ژمارەی مامەڵە دروستبکە ئەگەر نییە
            if (!$payment->transaction_number) {
                $payment->transaction_number = 'TRX-' . time() . '-' . rand(1000, 9999);
            }

            // ژمارەی سەرچاوە دروستبکە ئەگەر نییە
            if (!$payment->reference_number) {
                $payment->reference_number = 'REF-' . now()->format('Ymd') . '-' . rand(100, 999);
            }

            // چێککردنەوەی بڕی پارەدان لەگەڵ قەرز
            $customer = Customer::find($payment->customer_id);
            if ($customer && $payment->amount > $customer->current_debt) {
                throw new \Exception('بڕی پارەدان زیاترە لە کۆی قەرز!');
            }
        });

        static::created(function ($payment) {
            try {
                // ١. نوێکردنەوەی قەرزی کڕیار
                if ($payment->customer) {
                    $payment->customer->updateDebt();
                }

                // ٢. ئەگەر sale_id هەیە، نوێکردنەوەی ڕەوشتی فرۆشتن
                if ($payment->sale_id) {
                    $sale = $payment->sale;
                    if ($sale) {
                        $sale->paid_amount += $payment->amount;
                        $sale->remaining_amount -= $payment->amount;

                        if ($sale->remaining_amount <= 0) {
                            $sale->status = 'paid';
                            $sale->paid_date = $payment->payment_date;
                        } else {
                            $sale->status = 'partial';
                        }
                        $sale->save();
                    }
                }

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

                // ٤. تۆمارکردنی مامەڵە (ئەگەر Transaction Model هەیە)
                if (class_exists('App\Models\Transaction')) {
                    Transaction::create([
                        'transaction_number' => Transaction::generateTransactionNumber(),
                        'type' => 'credit_payment',
                        'amount' => $payment->amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $cash->balance,
                        'transactionable_type' => self::class,
                        'transactionable_id' => $payment->id,
                        'reference_number' => $payment->reference_number,
                        'description' => 'دانەوەی قەرز لەلایەن ' . ($payment->customer->name ?? 'کڕیار') . ' - ' . ($payment->notes ?? ''),
                        'transaction_date' => $payment->payment_date,
                        'created_by' => auth()->user()?->name ?? 'سیستەم',
                    ]);
                }

            } catch (\Exception $e) {
                \Log::error('Error in CreditPayment created: ' . $e->getMessage());
            }
        });
    }
}
