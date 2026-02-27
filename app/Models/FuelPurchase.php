<?php
// app/Models/FuelPurchase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\Cash;
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

    protected static function booted()
    {
        static::created(function ($purchase) {
            try {
                // ١. زیادکردنی بەنزین بۆ کۆگا
                if ($purchase->category) {
                    $purchase->category->updateStock($purchase->liters, 'add');
                }

                // ٢. کەمکردنەوەی پارە لە قاسە - ئەمە زۆر گرنگە
                $cash = Cash::first();

                // ئەگەر قاسە بوونی نییە، دروستی بکە
                if (!$cash) {
                    $cash = Cash::initialize(0);
                }

                // کەمکردنەوەی پارە لە قاسە
                $cash->addExpense($purchase->total_price);

            } catch (\Exception $e) {
                Log::error('Error in FuelPurchase created event: ' . $e->getMessage());
            }
        });
    }
}
