<?php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Cash;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'category_id', 'customer_id', 'payment_type',
        'liters', 'price_per_liter', 'total_price',
        'paid_amount', 'remaining_amount',
        'status', 'sale_date', 'due_date', 'paid_date', 'notes',
    ];

    protected $casts = [
        'liters'           => 'decimal:2',
        'price_per_liter'  => 'decimal:2',
        'total_price'      => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'sale_date'        => 'date',
        'due_date'         => 'date',
        'paid_date'        => 'date',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // لە نێو کلاسەکەدا زیاد بکە:
public function employee(): BelongsTo
{
    return $this->belongsTo(Employee::class);
}

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    // ─── Accessors ───────────────────────────────────────────────

    // ✅ قازانجی ئەم فرۆشتنە = (نرخ فرۆشتن - نرخ کڕین) × لیتر
    public function getProfitAttribute(): float
    {
        $purchasePrice = floatval($this->category?->purchase_price ?? 0);
        $salePrice     = floatval($this->price_per_liter);
        return ($salePrice - $purchasePrice) * floatval($this->liters);
    }

    public function getFormattedProfitAttribute(): string
    {
        return number_format($this->profit) . ' د.ع';
    }

    // ✅ ڕەوشتی قەرز
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'paid'    => '✅ پارەدراوە',
            'partial' => '⏳ بەشێکی پارەدراوە',
            'pending' => '⏰ چاوەڕوانی پارەدان',
            default   => '-',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'paid'    => 'success',
            'partial' => 'warning',
            'pending' => 'danger',
            default   => 'gray',
        };
    }

    // ─── Events ──────────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function ($sale) {
            // دڵنیابە لە remaining_amount
            if ($sale->payment_type === 'cash') {
                $sale->paid_amount      = $sale->total_price;
                $sale->remaining_amount = 0;
                $sale->status           = 'paid';
            } else {
                $sale->paid_amount      = $sale->paid_amount ?? 0;
                $sale->remaining_amount = $sale->total_price - ($sale->paid_amount ?? 0);
                $sale->status           = $sale->remaining_amount > 0 ? 'pending' : 'paid';
            }
        });

        static::created(function ($sale) {
            try {
                // ١. کەمکردنەوەی کۆگا
                if ($sale->category) {
                    $sale->category->updateStock(floatval($sale->liters), 'subtract');
                }

                // ٢. زیادکردنی پارە بۆ قاسە (تەنها فرۆشتنی ڕاستەوخۆ)
                if ($sale->payment_type === 'cash') {
                    $cash = Cash::first();
                    if ($cash) {
                        $balanceBefore         = $cash->balance;
                        $cash->balance        += $sale->total_price;
                        $cash->total_income   += $sale->total_price;
                        $cash->last_update     = now();
                        $cash->save();

                        // ٣. تۆمارکردنی مامەڵە
                        Transaction::create([
                            'transaction_number'   => Transaction::generateTransactionNumber(),
                            'type'                 => 'sale',
                            'amount'               => $sale->total_price,
                            'balance_before'       => $balanceBefore,
                            'balance_after'        => $cash->balance,
                            'transactionable_type' => self::class,
                            'transactionable_id'   => $sale->id,
                            'reference_number'     => $sale->id,
                            'description'          => 'فرۆشتنی ' . number_format($sale->liters, 0) . 'L ' .
                                                      ($sale->category->name ?? '') .
                                                      ' — قازانج: ' . number_format($sale->profit) . ' د.ع',
                            'transaction_date'     => $sale->sale_date,
                            'created_by'           => auth()->user()?->name ?? 'سیستەم',
                        ]);
                    }
                }

                // ٤. نوێکردنەوەی قەرزی کڕیار
                if ($sale->payment_type === 'credit' && $sale->customer) {
                    $sale->customer->updateDebt();
                }

            } catch (\Exception $e) {
                Log::error('Error in Sale created event: ' . $e->getMessage());
            }
        });

        static::deleted(function ($sale) {
            try {
                // گەڕاندنەوەی کۆگا
                if ($sale->category) {
                    $sale->category->updateStock(floatval($sale->liters), 'add');
                }

                // گەڕاندنەوەی پارە (تەنها فرۆشتنی ڕاستەوخۆ)
                if ($sale->payment_type === 'cash') {
                    $cash = Cash::first();
                    if ($cash) {
                        $cash->balance      -= $sale->total_price;
                        $cash->total_income -= $sale->total_price;
                        $cash->save();
                    }
                }

                // نوێکردنەوەی قەرزی کڕیار
                if ($sale->payment_type === 'credit' && $sale->customer) {
                    $sale->customer->updateDebt();
                }

            } catch (\Exception $e) {
                Log::error('Error in Sale deleted event: ' . $e->getMessage());
            }
        });
    }
}
