<?php
// app/Models/Category.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name', 'type_id', 'current_price', 'purchase_price', 'stock_liters',
    ];

    protected $casts = [
        'current_price'  => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock_liters'   => 'decimal:2',
    ];

    // ✅ قازانجی هەر لیترێک
    public function getProfitPerLiterAttribute(): float
    {
        return floatval($this->current_price) - floatval($this->purchase_price);
    }

    // ✅ قازانجی فۆرمات بووی هەر لیترێک
    public function getFormattedProfitPerLiterAttribute(): string
    {
        return number_format($this->profit_per_liter) . ' د.ع / لیتر';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function fuelPurchases(): HasMany
    {
        return $this->hasMany(FuelPurchase::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * نوێکردنەوەی کۆگا بە شێوازی ئاتۆمیک
     */
    public function updateStock(float $liters, string $type = 'add'): static
    {
        if ($liters <= 0) return $this;

        if ($type === 'add') {
            \Illuminate\Support\Facades\DB::table('categories')
                ->where('id', $this->id)
                ->increment('stock_liters', $liters);
        } else {
            \Illuminate\Support\Facades\DB::table('categories')
                ->where('id', $this->id)
                ->decrement('stock_liters', $liters);
        }

        $this->refresh();
        return $this;
    }

    public function getTypeNameAttribute(): string
    {
        return $this->type?->name ?? 'نەدیاریکراو';
    }

    public function getTypeKeyAttribute(): string
    {
        return $this->type?->key ?? 'unknown';
    }
}
