<?php
// app/Models/QuickSale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\User;
use App\Models\Cash;
use App\Models\Transaction;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        'total_liters',
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
        'total_liters' => 'decimal:2',
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
        $this->saveQuietly();

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
        $this->saveQuietly();

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
     * جێبەجێکردنی جیاوازیەکان بۆ کۆگا و قاسە
     */
    public function applyDifferencesToStockAndCash()
    {
        $differences = $this->differences ?? [];
        if (empty($differences)) {
            return [
                'applied' => false,
                'message' => 'هیچ جیاوازیەک نییە'
            ];
        }

        $categories = Category::all()->keyBy('id');
        $results = [
            'positive' => [],
            'negative' => [],
            'total_positive_amount' => 0,
            'total_negative_amount' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($differences as $catId => $diff) {
                if (abs($diff) < 0.01) continue;

                $category = $categories[$catId] ?? null;
                if (!$category) continue;

                $pricePerLiter = $category->current_price;
                $totalPrice = abs($diff) * $pricePerLiter;

                if ($diff > 0) {
                    // جیاوازی ئەرێنی (فرۆشراوی تۆ زیاترە)
                    // => بڕەکە لە کۆگا کەم دەکەینەوە و پارەکە دەچێتە قاسە

                    // کەمکردنەوە لە کۆگا
                    $category->updateStock($diff, 'subtract');

                    $results['positive'][] = [
                        'category' => $category->name,
                        'liters' => $diff,
                        'price' => $totalPrice
                    ];
                    $results['total_positive_amount'] += $totalPrice;

                } else {
                    // جیاوازی نەرێنی (فرۆشراوی تۆ کەمترە)
                    // => بڕەکە لە کۆگا دەمێنێتەوە، هیچ کارێک ناکەین

                    $results['negative'][] = [
                        'category' => $category->name,
                        'liters' => abs($diff),
                        'price' => $totalPrice
                    ];
                    $results['total_negative_amount'] += $totalPrice;
                }
            }

            // زیادکردنی پارە بۆ قاسە (ئەگەر جیاوازی ئەرێنی هەبێت)
            if ($results['total_positive_amount'] > 0) {
                $this->addMoneyToCash($results['total_positive_amount']);
            }

            DB::commit();

            return [
                'applied' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in applyDifferencesToStockAndCash: ' . $e->getMessage());

            return [
                'applied' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * زیادکردنی پارە بۆ قاسە
     */
    protected function addMoneyToCash($amount)
    {
        $cash = Cash::first();
        if (!$cash) {
            $cash = Cash::create([
                'balance' => 0,
                'total_income' => 0,
                'total_expense' => 0,
                'capital' => 0,
                'profit' => 0,
                'last_update' => now(),
            ]);
        }

        $balanceBefore = $cash->balance;
        $cash->balance += $amount;
        $cash->total_income += $amount;
        $cash->last_update = now();
        $cash->save();

        // تۆمارکردنی مامەڵە
        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'quick_sale_difference',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $cash->balance,
            'reference_number' => $this->id,
            'description' => "جیاوازی فرۆشی خێرا - شەفتی {$this->shift_name} - " . number_format($amount) . " د.ع",
            'transaction_date' => $this->sale_date,
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);

        return $cash;
    }

    /**
     * تۆمارکردنی فرۆشتن بۆ جیاوازیەکان
     */
    public function createSalesForDifferences()
    {
        $differences = $this->differences ?? [];
        if (empty($differences)) {
            return [];
        }

        $categories = Category::all()->keyBy('id');
        $sales = [];

        DB::beginTransaction();

        try {
            foreach ($differences as $catId => $diff) {
                if ($diff <= 0.01) continue; // تەنها جیاوازی ئەرێنی

                $category = $categories[$catId] ?? null;
                if (!$category) continue;

                $pricePerLiter = $category->current_price;
                $totalPrice = $diff * $pricePerLiter;

                // تۆمارکردنی فرۆشتن
                $sale = Sale::create([
                    'category_id' => $category->id,
                    'liters' => $diff,
                    'price_per_liter' => $pricePerLiter,
                    'total_price' => $totalPrice,
                    'sale_date' => $this->sale_date,
                    'payment_type' => 'cash',
                    'status' => 'paid',
                    'paid_amount' => $totalPrice,
                    'remaining_amount' => 0,
                    'notes' => "فرۆشتنی جیاوازی لە شەفتی {$this->shift_name}",
                ]);

                $sales[] = $sale;

                // پەیوەستکردنی transaction بە sale
                $transaction = Transaction::where('reference_number', $this->id)
                    ->where('type', 'quick_sale_difference')
                    ->latest()
                    ->first();

                if ($transaction) {
                    $transaction->transactionable_type = Sale::class;
                    $transaction->transactionable_id = $sale->id;
                    $transaction->save();
                }
            }

            DB::commit();

            return $sales;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in createSalesForDifferences: ' . $e->getMessage());

            return [];
        }
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
        $sold = $this->sold_data ?? [];

        if (!empty($sold)) {
            foreach ($sold as $liters) {
                $total += floatval($liters);
            }
        }
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
        $date = $date ?? Carbon::now()->format('Y-m-d');

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

    /**
     * وەرگرتنی کۆی گشتی بۆ ماوەی دیاریکراو
     */
    public static function getTotalsForDateRange($fromDate, $toDate)
    {
        $query = self::whereDate('sale_date', '>=', $fromDate)
                     ->whereDate('sale_date', '<=', $toDate);

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
            'total' => [
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

            $totals['total']['count']++;
            $totals['total']['total_liters'] += $sale->total_liters ?? 0;
            $totals['total']['total_amount'] += $sale->total_amount ?? 0;
        }

        return $totals;
    }
}
