<?php
// app/Models/FuelPurchase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\Cash;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class FuelPurchase extends Model
{
    protected $table = 'fuel_purchases';

    protected $fillable = [
        'category_id',
        'liters',
        'price_per_liter',
        'total_price',
        'purchase_date',
        'notes'
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'total_price' => 'decimal:2',
        'purchase_date' => 'date',
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
        static::created(function ($purchase) {
            try {
                // ١. زیادکردنی بەنزین بۆ کۆگا
                if ($purchase->category) {
                    $purchase->category->updateStock($purchase->liters, 'add');
                }

                // ٢. کەمکردنەوەی پارە لە قاسە
                $cash = Cash::first();
                if (!$cash) {
                    $cash = Cash::create([
                        'balance' => 0,
                        'total_income' => 0,
                        'total_expense' => 0,
                        'last_update' => now(),
                    ]);
                }

                // کەمکردنەوەی پارە لە قاسە
                $cash->addExpense($purchase->total_price);

                // ٣. تۆمارکردنی مامەڵە لە خشتەی transactions
                Transaction::recordTransaction([
                    'type' => 'purchase',
                    'amount' => $purchase->total_price,
                    'transactionable_type' => self::class,
                    'transactionable_id' => $purchase->id,
                    'reference_number' => $purchase->id,
                    'description' => 'کڕینی ' . number_format($purchase->liters) . ' لیتر ' . ($purchase->category->name ?? 'بەنزین') . ($purchase->notes ? ' - ' . $purchase->notes : ''),
                    'transaction_date' => $purchase->purchase_date,
                    'is_income' => false,
                ]);

            } catch (\Exception $e) {
                Log::error('Error in FuelPurchase created event: ' . $e->getMessage());
            }
        });
    }
}
