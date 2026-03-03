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
        'name',
        'type_id',
        'current_price',
        'purchase_price',
        'stock_liters'
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock_liters' => 'decimal:2',
    ];

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

    public function updateStock($liters, $type = 'add')
    {
        if ($type === 'add') {
            $this->stock_liters += $liters;
        } else {
            $this->stock_liters -= $liters;
        }
        $this->save();

        return $this;
    }

    /**
     * وەرگرتنی جۆری بەرهەم بە شێوازی ڕاستەوخۆ
     */
    public function getTypeNameAttribute()
    {
        return $this->type?->name ?? 'نەدیاریکراو';
    }

    public function getTypeKeyAttribute()
    {
        return $this->type?->key ?? 'unknown';
    }
}
