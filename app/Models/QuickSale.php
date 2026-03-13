<?php
// app/Models/QuickSale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'created_by',
    ];

    protected $casts = [
        'sale_date'        => 'date',
        'shift'            => 'string',
        'categories_data'  => 'array',
        'initial_readings' => 'array',
        'final_readings'   => 'array',
        'sold_data'        => 'array',
        'reported_sold'    => 'array',
        'differences'      => 'array',
        'total_amount'     => 'decimal:2',
        'total_liters'     => 'decimal:2',
    ];

    // ===================== Relations =====================

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===================== Accessors =====================

    public function getShiftNameAttribute(): string
    {
        return match($this->shift) {
            'morning' => '🌅 شەفتی بەیانی',
            'evening' => '🌙 شەفتی ئێوارە',
            default   => 'نادیار',
        };
    }

    public function getShiftColorAttribute(): string
    {
        return match($this->shift) {
            'morning' => 'warning',
            'evening' => 'info',
            default   => 'gray',
        };
    }

    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'open'   => 'کراوە',
            'closed' => 'داخراو',
            default  => 'نادیار',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open'   => 'success',
            'closed' => 'gray',
            default  => 'warning',
        };
    }

    // ===================== Static Helpers =====================

    public static function getTotalsByDate($date = null): array
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');

        $totals = [
            'morning' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'evening' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
        ];

        foreach (self::whereDate('sale_date', $date)->get() as $sale) {
            $shift = $sale->shift;
            if (isset($totals[$shift])) {
                $totals[$shift]['count']++;
                $totals[$shift]['total_liters'] += floatval($sale->total_liters ?? 0);
                $totals[$shift]['total_amount'] += floatval($sale->total_amount ?? 0);
            }
        }

        return $totals;
    }

    public static function getTotalsForDateRange($fromDate, $toDate): array
    {
        $totals = [
            'morning' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'evening' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'total'   => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
        ];

        $sales = self::whereDate('sale_date', '>=', $fromDate)
            ->whereDate('sale_date', '<=', $toDate)
            ->get();

        foreach ($sales as $sale) {
            $shift = $sale->shift;
            if (isset($totals[$shift])) {
                $totals[$shift]['count']++;
                $totals[$shift]['total_liters'] += floatval($sale->total_liters ?? 0);
                $totals[$shift]['total_amount'] += floatval($sale->total_amount ?? 0);

                $totals['total']['count']++;
                $totals['total']['total_liters'] += floatval($sale->total_liters ?? 0);
                $totals['total']['total_amount'] += floatval($sale->total_amount ?? 0);
            }
        }

        return $totals;
    }

    public static function getMorningFinalReadings($date)
    {
        $morning = self::whereDate('sale_date', $date)
            ->where('shift', 'morning')
            ->where('status', 'closed')
            ->first();

        return $morning ? $morning->final_readings : [];
    }

    public static function getCategoriesGroupedByType()
    {
        $grouped = [];
        foreach (Category::with('type')->get() as $category) {
            $typeKey = $category->type->key ?? 'other';
            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'name'       => $category->type->name  ?? 'ئەوانی تر',
                    'color'      => $category->type->color ?? 'gray',
                    'categories' => [],
                ];
            }
            $grouped[$typeKey]['categories'][$category->id] = [
                'id'    => $category->id,
                'name'  => $category->name,
                'price' => $category->current_price,
                'stock' => $category->stock_liters,
            ];
        }
        return $grouped;
    }

    public static function getAllCategoriesList()
    {
        $list = [];
        foreach (Category::with('type')->get() as $category) {
            $list[$category->id] = [
                'id'       => $category->id,
                'name'     => $category->name,
                'type'     => $category->type->name ?? 'نادیار',
                'type_key' => $category->type->key  ?? 'other',
                'price'    => $category->current_price,
                'stock'    => $category->stock_liters,
            ];
        }
        return $list;
    }

    // ===================== Calculations =====================

    /**
     * حسابکردنی فرۆشراوی هەر کاتیگۆریەک
     *
     * IMPORTANT:
     * - خوێندنەوەکان (initial - final) = کۆی هەردوو لای پەمپ
     * - فرۆشتنی ڕاستەقینە بۆ یەک شەفت = (initial - final) / 2
     * - بڕی پارە = (initial - final) × نرخ / 2
     */
    public function calculateSoldFromReadings(): array
    {
        $initial     = $this->initial_readings ?? [];
        $final       = $this->final_readings   ?? [];
        $sold        = [];
        $totalAmount = 0;
        $totalLiters = 0;

        foreach (Category::all() as $category) {
            $catId      = $category->id;
            $initialVal = floatval($initial[$catId] ?? 0);
            $finalVal   = floatval($final[$catId]   ?? 0);
            $fullDiff   = $initialVal - $finalVal;

            // ✅ ÷ 2 چونکە خوێندنەوەکان کۆی هەردوو لای پەمپن
            $soldLiters = $fullDiff / 2;
            $soldAmount = ($fullDiff * floatval($category->current_price)) / 2;

            $sold[$catId]  = $soldLiters;
            $totalAmount  += $soldAmount;
            $totalLiters  += $soldLiters;
        }

        $this->sold_data    = $sold;
        $this->total_amount = $totalAmount;
        $this->total_liters = $totalLiters;

        return compact('sold', 'totalAmount', 'totalLiters');
    }

    public function calculateDifferences(): array
    {
        $sold        = $this->sold_data     ?? [];
        $reported    = $this->reported_sold ?? [];
        $differences = [];

        foreach (Category::all() as $category) {
            $catId       = $category->id;
            $soldVal     = floatval($sold[$catId]     ?? 0);
            $reportedVal = floatval($reported[$catId] ?? $soldVal);

            $differences[$catId] = $reportedVal - $soldVal;
        }

        $this->differences = $differences;

        return $differences;
    }

    // ===================== Stock & Cash =====================

    /**
     * گەڕاندنەوەی مامەڵەکانی پێشوو
     */
    protected function reverseStockAndCashForPreviousTransactions(): void
    {
        $previousTransactions = Transaction::where('reference_number', $this->id)
            ->whereIn('type', ['quick_sale', 'quick_sale_difference'])
            ->get();

        if ($previousTransactions->isEmpty()) {
            Log::info("QuickSale ID {$this->id}: هیچ transaction ی پێشوو نییە");
            return;
        }

        // ✅ یەک جار بخوێنەوە — دەرەوەی لووپ
        $cash = Cash::first();

        // ✅ کۆی گشتی کەم بکەرەوە — یەک جار save
        $totalReverseAmount = 0;

        foreach ($previousTransactions as $transaction) {
            if ($transaction->transactionable_type === Sale::class && $transaction->transactionable_id) {
                $prevSale = Sale::find($transaction->transactionable_id);
                if ($prevSale && $prevSale->liters > 0) {
                    DB::table('categories')
                        ->where('id', $prevSale->category_id)
                        ->increment('stock_liters', floatval($prevSale->liters));

                    Log::info("Reverse کۆگا — cat_id {$prevSale->category_id}: +{$prevSale->liters}L");
                }
            }

            if ($transaction->amount > 0) {
                $totalReverseAmount += floatval($transaction->amount);
            }
        }

        // ✅ یەک جار کەمی بکەرەوە
        if ($cash && $totalReverseAmount > 0) {
            $cash->balance      -= $totalReverseAmount;
            $cash->total_income -= $totalReverseAmount;
            $cash->last_update   = now();
            $cash->save();
        }

        $saleIds = $previousTransactions
            ->filter(fn($t) => $t->transactionable_type === Sale::class && $t->transactionable_id)
            ->pluck('transactionable_id')
            ->toArray();

        if (!empty($saleIds)) {
            Sale::whereIn('id', $saleIds)->delete();
        }

        Transaction::where('reference_number', $this->id)
            ->whereIn('type', ['quick_sale', 'quick_sale_difference'])
            ->delete();

        Log::info("QuickSale ID {$this->id}: Reverse تەواو — " . count($saleIds) . " sales سڕایەوە");
    }

    /**
     * جێبەجێکردنی فرۆشتن بۆ کۆگا و قاسە
     */
    public function applyDifferencesToStockAndCash(): array
    {
        $this->calculateSoldFromReadings();
        $differences = $this->calculateDifferences();
        $sold        = $this->sold_data ?? [];

        $hasSold = collect($sold)->some(fn($v) => floatval($v) > 0.01);
        $hasDiff = collect($differences)->some(fn($v) => abs(floatval($v)) > 0.01);

        if (!$hasSold && !$hasDiff) {
            return ['applied' => false, 'message' => 'هیچ فرۆشتنێک نییە', 'details' => []];
        }

        $results = [
            'sold_details'          => [],
            'positive'              => [],
            'negative'              => [],
            'total_sold_amount'     => 0,
            'total_sold_liters'     => 0,
            'total_positive_amount' => 0,
            'total_negative_amount' => 0,
            'total_positive_liters' => 0,
            'total_negative_liters' => 0,
            'details'               => [],
        ];

        DB::beginTransaction();

        try {
            $this->reverseStockAndCashForPreviousTransactions();

            DB::table('quick_sales')->where('id', $this->id)->update([
                'sold_data'    => json_encode($sold),
                'differences'  => json_encode($differences),
                'total_amount' => $this->total_amount,
                'total_liters' => $this->total_liters,
            ]);

            $categories = Category::all()->keyBy('id');

            // ✅ یەک جار قاسەکە بخوێنەوە
            $cash = Cash::first() ?? Cash::create([
                'balance'       => 0,
                'total_income'  => 0,
                'total_expense' => 0,
                'capital'       => 0,
                'profit'        => 0,
                'last_update'   => now(),
            ]);

            // ✅ کۆی پارەکان کۆ بکەوە — دوای لووپ یەک جار save
            $totalCashToAdd = 0;

            // گام ١: کۆگا کەم بکەرەوە بەپێی sold_data
            foreach ($sold as $catId => $soldLiters) {
                $soldLiters = floatval($soldLiters);
                if ($soldLiters <= 0.01) continue;

                $category = $categories[$catId] ?? null;
                if (!$category) continue;

                $pricePerLiter = floatval($category->current_price);
                $totalPrice    = $soldLiters * $pricePerLiter;

                $currentStock = floatval(DB::table('categories')->where('id', $catId)->value('stock_liters') ?? 0);
                if ($currentStock < $soldLiters) {
                    throw new \Exception("بڕی پێویست لە کۆگای {$category->name}دا نییە");
                }

                DB::table('categories')->where('id', $catId)->decrement('stock_liters', $soldLiters);

                // ✅ تەنها Sale و Transaction — cash لێرەدا نەگۆڕدرێت
                $this->recordSaleAndTransaction($totalPrice, $category, $soldLiters, 'quick_sale', $cash);

                $totalCashToAdd += $totalPrice;

                $results['sold_details'][] = [
                    'category_name'   => $category->name,
                    'liters'          => $soldLiters,
                    'price_per_liter' => $pricePerLiter,
                    'total_price'     => $totalPrice,
                ];
                $results['total_sold_amount'] += $totalPrice;
                $results['total_sold_liters']  += $soldLiters;
                $results['details'][] = [
                    'type'          => 'sold',
                    'category_name' => $category->name,
                    'liters'        => $soldLiters,
                    'total_price'   => $totalPrice,
                    'message'       => "✅ {$soldLiters} لیتر {$category->name} - " . number_format($totalPrice) . " د.ع",
                ];
            }

            // گام ٢: جیاوازیەکان
            foreach ($differences as $catId => $diff) {
                if (abs($diff) < 0.01) continue;

                $category = $categories[$catId] ?? null;
                if (!$category) continue;

                $pricePerLiter = floatval($category->current_price);
                $totalPrice    = $diff * $pricePerLiter;

                if ($diff > 0) {
                    $currentStock = floatval(DB::table('categories')->where('id', $catId)->value('stock_liters') ?? 0);
                    if ($currentStock < $diff) {
                        throw new \Exception("بڕی پێویست لە کۆگای {$category->name}دا نییە بۆ جیاوازی");
                    }

                    DB::table('categories')->where('id', $catId)->decrement('stock_liters', $diff);

                    // ✅ تەنها Sale و Transaction — cash لێرەدا نەگۆڕدرێت
                    $this->recordSaleAndTransaction($totalPrice, $category, $diff, 'quick_sale_difference', $cash);

                    $totalCashToAdd += $totalPrice;

                    $results['positive'][] = ['category' => $category->name, 'liters' => $diff, 'price' => $totalPrice];
                    $results['total_positive_amount'] += $totalPrice;
                    $results['total_positive_liters']  += $diff;
                    $results['details'][] = [
                        'type'    => 'positive',
                        'message' => "⚕ جیاوازی زیادە: {$diff}L {$category->name} — " . number_format($totalPrice) . " د.ع",
                    ];
                } else {
                    $absLiters = abs($diff);
                    $absPrice  = abs($totalPrice);

                    $results['negative'][] = ['category' => $category->name, 'liters' => $absLiters, 'price' => $absPrice];
                    $results['total_negative_amount'] += $absPrice;
                    $results['total_negative_liters']  += $absLiters;
                    $results['details'][] = [
                        'type'    => 'negative',
                        'message' => "⚠️ {$category->name}: {$absLiters}L کەمتر - " . number_format($absPrice) . " د.ع",
                    ];
                }
            }

            // ✅ یەک جار قاسەکە نوێ بکەرەوە — هەرگیز دوو جار نابێت
            if ($totalCashToAdd > 0) {
                $balanceBefore      = floatval($cash->balance);
                $cash->balance      = $balanceBefore + $totalCashToAdd;
                $cash->total_income = floatval($cash->total_income) + $totalCashToAdd;
                $cash->last_update  = now();
                $cash->save();

                Log::info("QuickSale ID {$this->id}: قاسە نوێکرایەوە — +{$totalCashToAdd} دینار");
            }

            DB::commit();

            return [
                'applied' => true,
                'results' => $results,
                'message' => $this->generateResultMessage($results),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("QuickSale ID {$this->id}: " . $e->getMessage());
            return ['applied' => false, 'error' => $e->getMessage(), 'details' => []];
        }
    }

    /**
     * ✅ تەنها تۆمارکردنی Sale و Transaction
     * قاسە لێرەدا نەگۆڕدرێت — لە applyDifferencesToStockAndCash یەک جار تۆمار دەکرێت
     */
    protected function recordSaleAndTransaction(
        float    $amount,
        Category $category,
        float    $liters,
        string   $transactionType,
        Cash     $cash
    ): void {
        $description = $transactionType === 'quick_sale_difference'
            ? "جیاوازی - {$liters}L {$category->name} - {$this->shift_name}"
            : "فرۆشتن - {$liters}L {$category->name} - {$this->shift_name}";

        $transaction = Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type'               => $transactionType,
            'amount'             => $amount,
            'balance_before'     => floatval($cash->balance),
            'balance_after'      => floatval($cash->balance) + $amount,
            'reference_number'   => $this->id,
            'description'        => $description,
            'transaction_date'   => $this->sale_date,
            'created_by'         => auth()->user()?->name ?? 'سیستەم',
        ]);

        $sale = Sale::create([
            'category_id'      => $category->id,
            'liters'           => $liters,
            'price_per_liter'  => $category->current_price,
            'total_price'      => $amount,
            'sale_date'        => $this->sale_date,
            'payment_type'     => 'cash',
            'status'           => 'paid',
            'paid_amount'      => $amount,
            'remaining_amount' => 0,
            'notes'            => $description,
        ]);

        $transaction->transactionable_type = Sale::class;
        $transaction->transactionable_id   = $sale->id;
        $transaction->save();
    }

    /**
     * دروستکردنی پەیامی کۆتایی
     */
    protected function generateResultMessage(array $results): string
    {
        $lines = [];

        if ($results['total_sold_liters'] > 0) {
            $lines[] = "✅ **فرۆشتنی گشتی:**";
            $lines[] = "   - لیتر: " . number_format($results['total_sold_liters']) . " لیتر";
            $lines[] = "   - پارە: " . number_format($results['total_sold_amount']) . " دینار";
        }

        foreach ($results['details'] as $detail) {
            if ($detail['type'] === 'sold') {
                $lines[] = "   ✅ " . $detail['message'];
            }
        }

        if ($results['total_positive_liters'] > 0) {
            $lines[] = "⚕ **جیاوازی زیادە:**";
            $lines[] = "   - لیتر: " . number_format($results['total_positive_liters']) . "L";
            $lines[] = "   - پارە: " . number_format($results['total_positive_amount']) . " دینار";
        }

        if ($results['total_negative_liters'] > 0) {
            $lines[] = "⚠️ **کەمی فرۆشراو:**";
            $lines[] = "   - لیتر: " . number_format($results['total_negative_liters']) . "L";
            $lines[] = "   - پارە: " . number_format($results['total_negative_amount']) . " دینار کەم دەبێتەوە";
        }

        return implode("\n", $lines);
    }
}
