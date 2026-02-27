<?php
// app/Models/FuelPurchase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelPurchase extends Model
{
    protected $fillable = [
        'category_id', 'liters', 'price_per_liter', 'total_price', 'purchase_date', 'notes'
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
            // زیادکردنی بەنزین بۆ کۆگا
            $purchase->category->updateStock($purchase->liters, 'add');

            // کەمکردنەوەی پارە لە قاسە
            $cash = Cash::first();
            if ($cash) {
                $cash->addExpense($purchase->total_price);
            }
        });
    }
}
