<?php
// app/Models/QuickSale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\User;

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
        'reported_sold',
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
        'reported_sold' => 'array',
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
     * وەرگرتنی هەموو کاتیگۆریەکان بە پێی جۆر
     */
    public static function getCategoriesGroupedByType()
    {
        $categories = Category::with('type')->get();
        $grouped = [];

        foreach ($categories as $category) {
            $typeKey = $category->type->key ?? 'other';
            $typeName = $category->type->name ?? 'ئەوانی تر';

            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'name' => $typeName,
                    'color' => $category->type->color ?? 'gray',
                    'categories' => []
                ];
            }

            $grouped[$typeKey]['categories'][$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'price' => $category->current_price,
                'stock' => $category->stock_liters,
            ];
        }

        return $grouped;
    }

    /**
     * حسابکردنی فرۆشراوەکان و جیاوازی
     */
    public function calculateAll()
    {
        $initial = $this->initial_readings ?? [];
        $final = $this->final_readings ?? [];
        $reported = $this->reported_sold ?? [];

        $sold = [];
        $differences = [];
        $total = 0;

        $categories = Category::all();

        foreach ($categories as $category) {
            $catId = $category->id;
            $initialVal = floatval($initial[$catId] ?? 0);
            $finalVal = floatval($final[$catId] ?? 0);
            $reportedVal = floatval($reported[$catId] ?? 0);

            // فرۆشراو = سەرەتایی - کۆتایی
            $soldVal = $initialVal - $finalVal;
            $sold[$catId] = $soldVal;

            // جیاوازی = فرۆشراوی ڕاپۆرتکراو - فرۆشراوی حسابکراو
            $differences[$catId] = $reportedVal - $soldVal;

            // کۆی گشتی بە دینار
            $total += $soldVal * $category->current_price;
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
