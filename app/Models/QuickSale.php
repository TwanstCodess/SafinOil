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
        'total_liters', // زیادکردنی total_liters
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
        'total_liters' => 'decimal:2', // زیادکردنی total_liters
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
     * وەرگرتنی خوێندنەوەی کۆتایی شەفتی بەیانی بۆ شەفتی ئێوارە
     */
    public static function getMorningFinalReadings($date)
    {
        $morningShift = self::whereDate('sale_date', $date)
            ->where('shift', 'morning')
            ->first();

        return $morningShift ? $morningShift->final_readings : [];
    }

    /**
     * حسابکردنی فرۆشراوەکان لەسەر بنەمای خوێندنەوەکان
     */
    public function calculateSoldFromReadings()
    {
        $initial = $this->initial_readings ?? [];
        $final = $this->final_readings ?? [];
        $sold = [];
        $totalAmount = 0;
        $totalLiters = 0;

        $categories = Category::all();

        foreach ($categories as $category) {
            $catId = $category->id;
            $initialVal = floatval($initial[$catId] ?? 0);
            $finalVal = floatval($final[$catId] ?? 0);

            $soldVal = $initialVal - $finalVal;
            $sold[$catId] = $soldVal;
            $totalAmount += $soldVal * $category->current_price;
            $totalLiters += $soldVal;
        }

        $this->sold_data = $sold;
        $this->total_amount = $totalAmount;
        $this->total_liters = $totalLiters;
        $this->save();

        return [
            'sold' => $sold,
            'total_amount' => $totalAmount,
            'total_liters' => $totalLiters,
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
     * وەرگرتنی کۆی گشتی لیترەکان
     */
    public function getTotalLitersAttribute()
    {
        if (isset($this->attributes['total_liters'])) {
            return $this->attributes['total_liters'];
        }

        $total = 0;
        $readings = $this->readings ?? [];
        $sold = $this->sold_data ?? [];

        // ئەگەر sold_data هەیە، لەوێوە حساب بکە
        if (!empty($sold)) {
            foreach ($sold as $liters) {
                $total += floatval($liters);
            }
        }
        // ئەگەرنا لەسەر بنەمای خوێندنەوەکان حساب بکە
        elseif (!empty($this->initial_readings) && !empty($this->final_readings)) {
            foreach ($this->initial_readings as $catId => $initial) {
                $final = floatval($this->final_readings[$catId] ?? 0);
                $total += (floatval($initial) - $final);
            }
        }

        return $total;
    }

    /**
     * وەرگرتنی کۆی گشتی بەپێی بەروار
     */
    public static function getTotalsByDate($date = null)
    {
        $date = $date ?? now()->format('Y-m-d');

        $query = self::whereDate('sale_date', $date);

        $totals = [
            'morning' => [
                'count' => 0,
                'total_liters' => 0,
                'total_amount' => 0,
            ],
            'evening' => [
                'count' => 0,
                'total_liters' => 0,
                'total_amount' => 0,
            ],
        ];

        $sales = $query->get();

        foreach ($sales as $sale) {
            $shift = $sale->shift;
            $totals[$shift]['count']++;
            $totals[$shift]['total_liters'] += $sale->total_liters ?? 0;
            $totals[$shift]['total_amount'] += $sale->total_amount ?? 0;
        }

        return $totals;
    }
}
