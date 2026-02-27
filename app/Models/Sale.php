<?php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'category_id', 'liters', 'price_per_liter', 'total_price', 'sale_date'
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

    protected static function booted()
    {
        static::created(function ($sale) {
            // کەمکردنەوەی بەنزین لە کۆگا
            $sale->category->updateStock($sale->liters, 'subtract');

            // زیادکردنی پارە بۆ قاسە
            $cash = Cash::first();
            if ($cash) {
                $cash->addIncome($sale->total_price);
            }
        });
    }
}
