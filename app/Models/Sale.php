<?php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\Cash;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'category_id',
        'liters',
        'price_per_liter',
        'total_price',  // دڵنیابە لەوەی ئەمە لێرەدا بێت
        'sale_date'
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'total_price' => 'decimal:2',
        'sale_date' => 'date',
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
        static::creating(function ($sale) {
            // دڵنیابە لەوەی total_price حساب کراوە
            if (!$sale->total_price && $sale->liters && $sale->price_per_liter) {
                $sale->total_price = $sale->liters * $sale->price_per_liter;
            }
        });

        static::created(function ($sale) {
            // کەمکردنەوەی بەنزین لە کۆگا
            if ($sale->category) {
                $sale->category->updateStock($sale->liters, 'subtract');
            }

            // زیادکردنی پارە بۆ قاسە
            $cash = Cash::first();
            if ($cash) {
                $cash->addIncome($sale->total_price);
            }
        });
    }
}
