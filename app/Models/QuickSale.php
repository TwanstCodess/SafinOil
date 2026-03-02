<?php
// app/Models/QuickSale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickSale extends Model
{
    protected $table = 'quick_sales';

    protected $fillable = [
        'sale_date',
        'status',
        'categories_data',
        'initial_readings',
        'final_readings',
        'sold_data',
        'differences',
        'total_amount',
        'closed_by',
        'created_by'
    ];

    protected $casts = [
        'sale_date' => 'date',
        'categories_data' => 'array',
        'initial_readings' => 'array',
        'final_readings' => 'array',
        'sold_data' => 'array',
        'differences' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * وەرگرتنی هەموو کاتیگۆریەکان
     */
    public static function getAllCategories()
    {
        return Category::with('type')->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->name ?? 'نادیار',
                'type_key' => $category->type->key ?? 'unknown',
                'current_price' => $category->current_price,
                'stock' => $category->stock_liters,
            ];
        });
    }

    /**
     * پێکهاتنی ستراکچەری کاتیگۆریەکان
     */
    public static function getCategoriesStructure()
    {
        $categories = self::getAllCategories();
        $structure = [];

        foreach ($categories as $category) {
            $structure[$category['type_key']][$category['id']] = [
                'name' => $category['name'],
                'price' => $category['current_price'],
                'initial' => 0,
                'final' => 0,
                'sold' => 0,
                'difference' => 0,
            ];
        }

        return $structure;
    }

    /**
     * حسابکردنی فرۆشراوەکان و جیاوازی
     */
    public function calculateSold()
    {
        $initial = $this->initial_readings ?? [];
        $final = $this->final_readings ?? [];
        $sold = [];
        $differences = [];
        $total = 0;

        foreach ($this->categories_data ?? [] as $typeKey => $typeCategories) {
            foreach ($typeCategories as $catId => $catData) {
                $initialVal = $initial[$catId] ?? 0;
                $finalVal = $final[$catId] ?? 0;
                $soldVal = $initialVal - $finalVal;

                $sold[$catId] = $soldVal;
                $differences[$catId] = $soldVal - ($catData['sold'] ?? 0);

                $total += $soldVal * ($catData['price'] ?? 0);
            }
        }

        $this->sold_data = $sold;
        $this->differences = $differences;
        $this->total_amount = $total;
        $this->save();

        return [
            'sold' => $sold,
            'differences' => $differences,
            'total' => $total,
        ];
    }
}
