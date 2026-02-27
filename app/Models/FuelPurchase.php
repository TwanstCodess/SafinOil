<?php
// app/Models/FuelPurchase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\Cash;

class FuelPurchase extends Model
{
    protected $table = 'fuel_purchases';

    protected $fillable = [
        'category_id',
        'liters',
        'price_per_liter',
        'total_price',  // دڵنیابە لەوەی ئەمە لێرەدا بێت
        'purchase_date',
        'notes'
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'total_price' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    protected $attributes = [
        'total_price' => 0, // دیفۆڵت 0 بۆ total_price
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function booted()
    {
        static::creating(function ($purchase) {
            // دڵنیابە لەوەی total_price حساب کراوە
            if (!$purchase->total_price && $purchase->liters && $purchase->price_per_liter) {
                $purchase->total_price = $purchase->liters * $purchase->price_per_liter;
            }
        });

        static::created(function ($purchase) {
            // زیادکردنی بەنزین بۆ کۆگا
            if ($purchase->category) {
                $purchase->category->updateStock($purchase->liters, 'add');
            }

            // کەمکردنەوەی پارە لە قاسە
            $cash = Cash::first();
            if ($cash) {
                $cash->addExpense($purchase->total_price);
            }
        });
    }
}
