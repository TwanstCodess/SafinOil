<?php
// app/Models/Category.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name', 'type', 'current_price', 'purchase_price', 'stock_liters'
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock_liters' => 'decimal:2',
    ];

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
    }
}
