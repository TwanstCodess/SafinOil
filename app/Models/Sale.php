<?php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\Cash;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'category_id',
        'liters',
        'price_per_liter',
        'total_price',
        'sale_date'
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'total_price' => 'decimal:2',
        'sale_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    protected static function booted()
    {
        static::created(function ($sale) {
            try {
                // ١. کەمکردنەوەی بەنزین لە کۆگا
                if ($sale->category) {
                    $sale->category->updateStock($sale->liters, 'subtract');
                }

                // ٢. زیادکردنی پارە بۆ قاسە
                $cash = Cash::first();
                if (!$cash) {
                    $cash = Cash::create([
                        'balance' => 0,
                        'total_income' => 0,
                        'total_expense' => 0,
                        'last_update' => now(),
                    ]);
                }

                // زیادکردنی پارە بۆ قاسە
                $cash->addIncome($sale->total_price);

                // ٣. تۆمارکردنی مامەڵە لە خشتەی transactions
                Transaction::recordTransaction([
                    'type' => 'sale',
                    'amount' => $sale->total_price,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $sale->id,
                    'reference_number' => $sale->id,
                    'description' => 'فرۆشتنی ' . number_format($sale->liters) . ' لیتر ' . ($sale->category->name ?? 'بەنزین'),
                    'transaction_date' => $sale->sale_date,
                    'is_income' => true,
                ]);

            } catch (\Exception $e) {
                Log::error('Error in Sale created event: ' . $e->getMessage());
            }
        });
    }
}
