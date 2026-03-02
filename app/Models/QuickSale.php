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
        'shift',
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
        'shift' => 'string',
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
     * وەرگرتنی ناوی شەفت بە کوردی
     */
    public function getShiftNameAttribute(): string
    {
        return match($this->shift) {
            'morning' => 'شەفتی بەیانی',
            'evening' => 'شەفتی ئێوارە',
            default => 'نادیار',
        };
    }

    /**
     * وەرگرتنی ڕەنگی شەفت
     */
    public function getShiftColorAttribute(): string
    {
        return match($this->shift) {
            'morning' => 'warning',
            'evening' => 'info',
            default => 'gray',
        };
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
            $typeColor = $category->type->color ?? 'gray';

            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'name' => $typeName,
                    'color' => $typeColor,
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
     * وەرگرتنی هەموو کاتیگۆریەکان وەک لیست
     */
    public static function getAllCategoriesList()
    {
        $categories = Category::with('type')->get();
        $list = [];

        foreach ($categories as $category) {
            $list[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->name ?? 'نادیار',
                'type_key' => $category->type->key ?? 'other',
                'price' => $category->current_price,
                'stock' => $category->stock_liters,
            ];
        }

        return $list;
    }

    /**
     * وەرگرتنی ڕەنگی بۆ دیزاین
     */
    public function getTypeColor($typeKey)
    {
        return match($typeKey) {
            'fuel' => 'warning',
            'oil' => 'success',
            'gas' => 'info',
            default => 'gray',
        };
    }

    /**
     * حسابکردنی فرۆشراوەکان لەسەر بنەمای خوێندنەوەکان
     */
    public function calculateSoldFromReadings()
    {
        $initial = $this->initial_readings ?? [];
        $final = $this->final_readings ?? [];
        $sold = [];
        $total = 0;

        $categories = Category::all();

        foreach ($categories as $category) {
            $catId = $category->id;
            $initialVal = floatval($initial[$catId] ?? 0);
            $finalVal = floatval($final[$catId] ?? 0);

            $soldVal = $initialVal - $finalVal;
            $sold[$catId] = $soldVal;
            $total += $soldVal * $category->current_price;
        }

        $this->sold_data = $sold;
        $this->total_amount = $total;
        $this->save();

        return [
            'sold' => $sold,
            'total' => $total,
        ];
    }

    /**
     * حسابکردنی جیاوازی لەگەڵ فرۆشراوەکانی تۆ
     */
    public function calculateDifferences()
    {
        $sold = $this->sold_data ?? [];
        $reported = $this->reported_sold ?? [];
        $differences = [];

        $categories = Category::all();

        foreach ($categories as $category) {
            $catId = $category->id;
            $soldVal = floatval($sold[$catId] ?? 0);
            $reportedVal = floatval($reported[$catId] ?? $soldVal);

            $differences[$catId] = $reportedVal - $soldVal;
        }

        $this->differences = $differences;
        $this->save();

        return $differences;
    }

    /**
     * هەموو حسابەکان یەکجار
     */
    public function calculateAll()
    {
        $this->calculateSoldFromReadings();
        $this->calculateDifferences();

        return $this;
    }

    /**
     * کۆی گشتی ڕۆژ بۆ هەردوو شەفت
     */
    public static function getDailyTotal($date)
    {
        $morning = self::whereDate('sale_date', $date)
            ->where('shift', 'morning')
            ->first();

        $evening = self::whereDate('sale_date', $date)
            ->where('shift', 'evening')
            ->first();

        $total = 0;
        $total += $morning->total_amount ?? 0;
        $total += $evening->total_amount ?? 0;

        return $total;
    }
}
