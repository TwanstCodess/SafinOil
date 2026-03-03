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

    public function getShiftNameAttribute(): string
    {
        return match($this->shift) {
            'morning' => 'شەفتی بەیانی',
            'evening' => 'شەفتی ئێوارە',
            default => 'نادیار',
        };
    }

    public function getShiftColorAttribute(): string
    {
        return match($this->shift) {
            'morning' => 'warning',
            'evening' => 'info',
            default => 'gray',
        };
    }

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

    public static function getMorningFinalReadings($date)
    {
        $morningShift = self::whereDate('sale_date', $date)
            ->where('shift', 'morning')
            ->first();

        return $morningShift ? $morningShift->final_readings : [];
    }

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
     * گەڕاندنەوەی مامەڵەکانی پێشوو بۆ کۆگا و قاسە
     */
    protected function revertPreviousTransactions()
    {
        // وەرگرتنی هەموو فرۆشتنەکانی پێشووی ئەم شەفتە
        $previousSales = Sale::where('notes', 'LIKE', "%شەفتی {$this->shift_name}%")
            ->whereDate('sale_date', $this->sale_date)
            ->get();

        if ($previousSales->isEmpty()) {
            Log::info("هیچ فرۆشتنێکی پێشوو نییە بۆ ئەم شەفتە");
            return;
        }

        // گەڕاندنەوەی کۆگا بۆ بڕەکانی پێشوو
        foreach ($previousSales as $sale) {
            $category = Category::find($sale->category_id);
            if ($category) {
                // زیادکردنەوەی ئەو بڕانەی پێشتر کەم کرابوون
                $category->updateStock($sale->liters, 'add');
                Log::info("گەڕاندنەوەی کۆگا - {$category->name}: +{$sale->liters} لیتر");
            }
        }

        // گەڕاندنەوەی قاسە
        $previousTransactions = Transaction::where('reference_number', $this->id)
            ->where('type', 'quick_sale_difference')
            ->get();

        $cash = Cash::first();
        if ($cash && $previousTransactions->isNotEmpty()) {
            foreach ($previousTransactions as $transaction) {
                $cash->balance -= $transaction->amount;
                $cash->total_income -= $transaction->amount;
                Log::info("گەڕاندنەوەی قاسە: -{$transaction->amount} دینار");
            }
            $cash->last_update = now();
            $cash->save();
        }

        // سڕینەوەی فرۆشتن و مامەڵەکانی پێشوو
        Sale::where('notes', 'LIKE', "%شەفتی {$this->shift_name}%")
            ->whereDate('sale_date', $this->sale_date)
            ->delete();

        Transaction::where('reference_number', $this->id)
            ->where('type', 'quick_sale_difference')
            ->delete();

        Log::info("مامەڵەکانی پێشوو سڕانەوە بۆ شەفتی {$this->shift_name}");
    }

    /**
     * جێبەجێکردنی جیاوازیەکان بۆ کۆگا و قاسە
     */
    public function applyDifferencesToStockAndCash()
    {
        // یەکەم: حسابکردنی فرۆشراوەکان و جیاوازیەکان
        $this->calculateSoldFromReadings();
        $differences = $this->calculateDifferences();

        if (empty($differences)) {
            return [
                'applied' => false,
                'message' => 'هیچ جیاوازیەک نییە',
                'details' => []
            ];
        }

        DB::beginTransaction();

        try {
            // *** گەڕاندنەوەی مامەڵەکانی پێشوو ***
            $this->revertPreviousTransactions();

            $categories = Category::all()->keyBy('id');
            $results = [
                'positive' => [],
                'negative' => [],
                'total_positive_amount' => 0,
                'total_negative_amount' => 0,
                'total_positive_liters' => 0,
                'total_negative_liters' => 0,
                'details' => []
            ];

            foreach ($differences as $catId => $diff) {
                if (abs($diff) < 0.01) continue;

                $category = $categories[$catId] ?? null;
                if (!$category) continue;

                // دووبارەخوێندنەوەی کاتیگۆری بۆ بەدەستهێنانی نوێترین بڕی کۆگا
                $category->refresh();

                $pricePerLiter = $category->current_price;
                $totalPrice = $diff * $pricePerLiter; // بڕی ڕاستەقینە

                if ($diff > 0) {
                    // جیاوازی ئەرێنی (فرۆشراوی تۆ زیاترە)

                    // پێش کەمکردنەوە، دڵنیابە کە بڕی پێویست لە کۆگا هەیە
                    if ($category->stock_liters < $diff) {
                        throw new \Exception("بڕی پێویست لە کۆگای {$category->name}دا نییە. بڕی ماوە: {$category->stock_liters} لیتر، پێویستە: {$diff} لیتر");
                    }

                    // کەمکردنەوە لە کۆگا
                    $category->updateStock($diff, 'subtract');
                    Log::info("کەمکردنەوە لە کۆگای {$category->name}: {$diff} لیتر");

                    // زیادکردنی پارە بۆ قاسە
                    $this->addMoneyToCash($totalPrice, $category, $diff);

                    $results['positive'][] = [
                        'category' => $category->name,
                        'liters' => $diff,
                        'price' => $totalPrice,
                        'price_per_liter' => $pricePerLiter
                    ];
                    $results['total_positive_amount'] += $totalPrice;
                    $results['total_positive_liters'] += $diff;

                    $results['details'][] = [
                        'type' => 'positive',
                        'category_id' => $catId,
                        'category_name' => $category->name,
                        'liters' => $diff,
                        'price_per_liter' => $pricePerLiter,
                        'total_price' => $totalPrice,
                        'message' => "✅ {$diff} لیتر {$category->name} فرۆشرا بە " . number_format($totalPrice) . " دینار"
                    ];

                } else {
                    // جیاوازی نەرێنی (فرۆشراوی تۆ کەمترە)
                    $absDiff = abs($diff);
                    $totalPrice = $absDiff * $pricePerLiter;

                    $results['negative'][] = [
                        'category' => $category->name,
                        'liters' => $absDiff,
                        'price' => $totalPrice,
                        'price_per_liter' => $pricePerLiter
                    ];
                    $results['total_negative_amount'] += $totalPrice;
                    $results['total_negative_liters'] += $absDiff;

                    $results['details'][] = [
                        'type' => 'negative',
                        'category_id' => $catId,
                        'category_name' => $category->name,
                        'liters' => $absDiff,
                        'price_per_liter' => $pricePerLiter,
                        'total_price' => $totalPrice,
                        'message' => "⚠️ {$category->name}: {$absDiff} لیتر کەمتر فرۆشراوە، لە کۆگا دەمێنێتەوە"
                    ];
                }
            }

            DB::commit();

            return [
                'applied' => true,
                'results' => $results,
                'message' => $this->generateResultMessage($results)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in applyDifferencesToStockAndCash: ' . $e->getMessage());

            return [
                'applied' => false,
                'error' => $e->getMessage(),
                'details' => []
            ];
        }
    }

    protected function addMoneyToCash($amount, $category, $liters)
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

        Log::info("زیادکردنی پارە بۆ قاسە: {$amount} دینار - بۆ {$liters} لیتر {$category->name}");

        $transaction = Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'quick_sale_difference',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $cash->balance,
            'reference_number' => $this->id,
            'description' => "فرۆشتنی جیاوازی - {$liters} لیتر {$category->name} - شەفتی {$this->shift_name}",
            'transaction_date' => $this->sale_date,
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);

        $sale = Sale::create([
            'category_id' => $category->id,
            'liters' => $liters,
            'price_per_liter' => $category->current_price,
            'total_price' => $amount,
            'sale_date' => $this->sale_date,
            'payment_type' => 'cash',
            'status' => 'paid',
            'paid_amount' => $amount,
            'remaining_amount' => 0,
            'notes' => "فرۆشتنی جیاوازی لە شەفتی {$this->shift_name} - {$category->name}",
        ]);

        $transaction->transactionable_type = Sale::class;
        $transaction->transactionable_id = $sale->id;
        $transaction->save();

        return $cash;
    }

    protected function generateResultMessage($results)
    {
        $message = [];

        if ($results['total_positive_liters'] > 0) {
            $message[] = "✅ فرۆشتنی زیادە: " . number_format($results['total_positive_liters']) . " لیتر - " . number_format($results['total_positive_amount']) . " دینار زیاد کرا بۆ قاسە";
        }

        if ($results['total_negative_liters'] > 0) {
            $message[] = "⚠️ کەمی فرۆشراو: " . number_format($results['total_negative_liters']) . " لیتر - " . number_format($results['total_negative_amount']) . " دینار لە کۆگا دەمێنێتەوە";
        }

        foreach ($results['details'] as $detail) {
            $message[] = "   " . $detail['message'];
        }

        return implode("\n", $message);
    }

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
        } elseif (!empty($this->initial_readings) && !empty($this->final_readings)) {
            foreach ($this->initial_readings as $catId => $initial) {
                $final = floatval($this->final_readings[$catId] ?? 0);
                $total += (floatval($initial) - $final);
            }
        }

        return $total;
    }

    public static function getTotalsByDate($date = null)
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');

        $query = self::whereDate('sale_date', $date);

        $totals = [
            'morning' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'evening' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
        ];

        foreach ($query->get() as $sale) {
            $shift = $sale->shift;
            $totals[$shift]['count']++;
            $totals[$shift]['total_liters'] += $sale->total_liters ?? 0;
            $totals[$shift]['total_amount'] += $sale->total_amount ?? 0;
        }

        return $totals;
    }

    public static function getTotalsForDateRange($fromDate, $toDate)
    {
        $query = self::whereDate('sale_date', '>=', $fromDate)
                     ->whereDate('sale_date', '<=', $toDate);

        $totals = [
            'morning' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'evening' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'total'   => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
        ];

        foreach ($query->get() as $sale) {
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
