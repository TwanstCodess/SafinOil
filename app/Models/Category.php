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
        'stock_liters',
    ];

    protected $casts = [
        'current_price'  => 'decimal:2',
        'purchase_price' => 'decimal:2',
        // *** چارەسەر: decimal:2 بەکاربێنە نەک integer ***
        // چونکە migration دا decimal(15,2) دەبێت
        'stock_liters'   => 'decimal:2',
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

    /**
     * نوێکردنەوەی کۆگا
     *
     * *** چارەسەر: DB::table بەکاربێنە بەجیاتی $this->save() ***
     * ئەمە دڵنیادەکات کە بەهاکە ڕاستەوخۆ لە DB نوێدەبێت
     * و کێشەی integer/decimal تایپ نابێت
     */
    public function updateStock(float $liters, string $type = 'add'): static
    {
        if ($liters <= 0) {
            return $this;
        }

        if ($type === 'add') {
            // زیادکردن بەشێوەی ئاتۆمیک لە DB
            \Illuminate\Support\Facades\DB::table('categories')
                ->where('id', $this->id)
                ->increment('stock_liters', $liters);

            $this->stock_liters = floatval($this->stock_liters) + $liters;
        } else {
            // کەمکردنەوە بەشێوەی ئاتۆمیک لە DB
            \Illuminate\Support\Facades\DB::table('categories')
                ->where('id', $this->id)
                ->decrement('stock_liters', $liters);

            $this->stock_liters = floatval($this->stock_liters) - $liters;
        }

        // نوێکردنەوەی مۆدێل لە DB بۆ ئەوەی stock_liters نوێترین بەها بێت
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
