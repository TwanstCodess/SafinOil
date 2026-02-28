<?php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Category;
use App\Models\Cash;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'category_id',
        'customer_id',
        'liters',
        'price_per_liter',
        'total_price',
        'sale_date',
        'payment_type',
        'status',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'paid_date'
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'sale_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    protected static function booted()
    {
        static::creating(function ($sale) {
            // دیاریکردنی بڕی ماوە بۆ قەرز
            if ($sale->payment_type === 'credit') {
                $sale->status = 'pending';
                $sale->paid_amount = 0;
                $sale->remaining_amount = $sale->total_price;
            } else {
                $sale->status = 'paid';
                $sale->paid_amount = $sale->total_price;
                $sale->remaining_amount = 0;
                $sale->paid_date = $sale->sale_date;
            }
        });

        static::created(function ($sale) {
            try {
                // ١. کەمکردنەوەی بەنزین لە کۆگا
                if ($sale->category) {
                    $sale->category->updateStock($sale->liters, 'subtract');
                }

                // ٢. زیادکردنی پارە بۆ قاسە (ئەگەر قەرز نییە)
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

                // ئەگەر فرۆشتن بە پارەی ڕاستەوخۆ بوو
                if ($sale->payment_type === 'cash') {
                    $cash->balance += $sale->total_price;
                    $cash->total_income += $sale->total_price;
                    $description = 'فرۆشتنی ' . number_format($sale->liters) . ' لیتر ' . ($sale->category->name ?? 'بەنزین') . ' - پارەی ڕاستەوخۆ';
                }
                // ئەگەر فرۆشتن بە قەرز بوو
                else {
                    // نوێکردنەوەی قەرزی کڕیار
                    if ($sale->customer) {
                        $sale->customer->updateDebt();
                    }
                    $description = 'فرۆشتنی قەرز - ' . number_format($sale->liters) . ' لیتر ' . ($sale->category->name ?? 'بەنزین') . ' - قەرزدار: ' . ($sale->customer->name ?? 'نادیار');
                }

                $cash->last_update = now();
                $cash->save();

                // ٣. تۆمارکردنی مامەڵە
                Transaction::create([
                    'transaction_number' => Transaction::generateTransactionNumber(),
                    'type' => $sale->payment_type === 'cash' ? 'sale' : 'credit_sale',
                    'amount' => $sale->total_price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $cash->balance,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $sale->id,
                    'reference_number' => $sale->id,
                    'description' => $description,
                    'transaction_date' => $sale->sale_date,
                    'created_by' => auth()->user()?->name ?? 'سیستەم',
                ]);

            } catch (\Exception $e) {
                Log::error('Error in Sale created event: ' . $e->getMessage());
            }
        });
    }

    /**
     * وەرگرتنی ڕەنگی ڕەوشت
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'paid' => 'success',
            'partial' => 'warning',
            'pending' => 'danger',
            default => 'gray',
        };
    }

    /**
     * وەرگێڕانی ڕەوشت
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'paid' => 'پارەدراوە',
            'partial' => 'بەشێکی پارەدراوە',
            'pending' => 'چاوەڕوانی پارەدان',
            default => 'نادیار',
        };
    }
}
