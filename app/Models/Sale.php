<?php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\Cash;
use Illuminate\Support\Facades\Log; // بۆ logging ئەگەر ویستت

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'category_id',
        'liters',
        'price_per_liter',
        'total_price',
        'sale_date'
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
            try {
                // ١. کەمکردنەوەی بەنزین لە کۆگا
                if ($sale->category) {
                    $sale->category->updateStock($sale->liters, 'subtract');
                }

                // ٢. زیادکردنی پارە بۆ قاسە - ئەمە زۆر گرنگە
                $cash = Cash::first();

                // ئەگەر قاسە بوونی نییە، دروستی بکە
                if (!$cash) {
                    $cash = Cash::initialize(0);
                }

                // زیادکردنی پارە بۆ قاسە
                $cash->addIncome($sale->total_price);

            } catch (\Exception $e) {
                // لە کاتی هەڵەدا، logging بکە
                Log::error('Error in Sale created event: ' . $e->getMessage());
            }
        });
    }
}
