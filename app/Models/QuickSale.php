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
        'sale_date', 'shift', 'status', 'categories_data',
        'initial_readings', 'final_readings', 'sold_data',
        'reported_sold', 'differences', 'total_amount',
        'total_liters', 'closed_by', 'created_by',
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
            'morning' => 'شەفتی بەیانی',
            'evening' => 'شەفتی ئێوارە',
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

    public function getTotalLitersAttribute()
    {
        if (isset($this->attributes['total_liters'])) {
            return $this->attributes['total_liters'];
        }
        $total = 0;
        $sold  = $this->sold_data ?? [];
        if (!empty($sold)) {
            foreach ($sold as $liters) {
                $total += floatval($liters);
            }
        } elseif (!empty($this->initial_readings) && !empty($this->final_readings)) {
            foreach ($this->initial_readings as $catId => $initial) {
                $total += floatval($initial) - floatval($this->final_readings[$catId] ?? 0);
            }
        }
        return $total;
    }

    // ===================== Static Helpers =====================

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

    public static function getMorningFinalReadings($date)
    {
        $morning = self::whereDate('sale_date', $date)->where('shift', 'morning')->first();
        return $morning ? $morning->final_readings : [];
    }

    public static function getTotalsByDate($date = null)
    {
        $date   = $date ?? Carbon::now()->format('Y-m-d');
        $totals = [
            'morning' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'evening' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
        ];
        foreach (self::whereDate('sale_date', $date)->get() as $sale) {
            $totals[$sale->shift]['count']++;
            $totals[$sale->shift]['total_liters'] += $sale->total_liters ?? 0;
            $totals[$sale->shift]['total_amount'] += $sale->total_amount ?? 0;
        }
        return $totals;
    }

    public static function getTotalsForDateRange($fromDate, $toDate)
    {
        $totals = [
            'morning' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'evening' => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
            'total'   => ['count' => 0, 'total_liters' => 0, 'total_amount' => 0],
        ];
        foreach (
            self::whereDate('sale_date', '>=', $fromDate)
                ->whereDate('sale_date', '<=', $toDate)->get() as $sale
        ) {
            $totals[$sale->shift]['count']++;
            $totals[$sale->shift]['total_liters'] += $sale->total_liters ?? 0;
            $totals[$sale->shift]['total_amount'] += $sale->total_amount ?? 0;
            $totals['total']['count']++;
            $totals['total']['total_liters'] += $sale->total_liters ?? 0;
            $totals['total']['total_amount'] += $sale->total_amount ?? 0;
        }
        return $totals;
    }

    // ===================== Calculations =====================

    /**
     * حسابکردنی فرۆشراوی هەر کاتیگۆریەک
     * sold = initial - final
     * *** ئەم فەنکشنە sold_data لە model دەنووسێت بەڵام DB save نادات ***
     * *** DB save تەنها لە applyDifferencesToStockAndCash() دەکرێت ***
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
            $soldVal    = $initialVal - $finalVal;

            $sold[$catId]  = $soldVal;
            $totalAmount  += $soldVal * floatval($category->current_price);
            $totalLiters  += $soldVal;
        }

        // تەنها لە model دەنووسێت — DB save لاحق دەکرێت
        $this->sold_data    = $sold;
        $this->total_amount = $totalAmount;
        $this->total_liters = $totalLiters;

        return compact('sold', 'totalAmount', 'totalLiters');
    }

    /**
     * حسابکردنی جیاوازی
     * diff = reported - sold
     * ئەگەر reported خاوێن بێت، diff = 0
     */
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
     * Reverse مامەڵەکانی پێشووی ئەم quick sale
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

        $cash = Cash::first();

        foreach ($previousTransactions as $transaction) {
            if (
                $transaction->transactionable_type === Sale::class &&
                $transaction->transactionable_id
            ) {
                $prevSale = Sale::find($transaction->transactionable_id);
                if ($prevSale && $prevSale->liters > 0) {
                    DB::table('categories')
                        ->where('id', $prevSale->category_id)
                        ->increment('stock_liters', floatval($prevSale->liters));

                    Log::info("Reverse کۆگا — cat_id {$prevSale->category_id}: +{$prevSale->liters}L");
                }
            }

            if ($cash && $transaction->amount > 0) {
                $cash->balance      -= floatval($transaction->amount);
                $cash->total_income -= floatval($transaction->amount);
                $cash->last_update   = now();
                $cash->save();
            }
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
     *
     * *** چارەسەری دووجار گرتنەوە ***
     * پێش هەموو شتێک، چێک دەکات ئایا transactions ی ئەم
     * request دا پێشتر تۆمارکراون یان نا.
     * بۆ ئەمەش پشت بە DB lock دەبەستێت نەک cache.
     */
    public function applyDifferencesToStockAndCash(): array
    {
        // --- حسابکردنی sold و جیاوازیەکان ---
        $this->calculateSoldFromReadings();
        $differences = $this->calculateDifferences();

        $sold = $this->sold_data ?? [];

        // ئەگەر هیچ فرۆشتنێک نییە، دەگەڕێینەوە
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
            // گام ١: Reverse مامەڵەکانی کۆن
            $this->reverseStockAndCashForPreviousTransactions();

            // *** گرینگ: پاش reverse، sold_data و differences دووبارە حساب بکە ***
            // چونکە reverseStockAndCashForPreviousTransactions نوێکردنەوەی DB دەکات
            // بەڵام $this->sold_data پێشتر حساب کرابوو، پێویست نییە دووبارە بکرێت
            // تەنها DB save بکە بۆ ئەوەی نوێترین داتا بمێنێتەوە
            DB::table('quick_sales')->where('id', $this->id)->update([
                'sold_data'    => json_encode($sold),
                'differences'  => json_encode($differences),
                'total_amount' => $this->total_amount,
                'total_liters' => $this->total_liters,
            ]);

            // داتای کاتیگۆریەکان لە DB بخوێنەرەوە دوای reverse
            $categories = Category::all()->keyBy('id');

            // گام ٢: کۆگا کەم بکەرەوە بەپێی sold_data
            foreach ($sold as $catId => $soldLiters) {
                $soldLiters = floatval($soldLiters);
                if ($soldLiters <= 0.01) {
                    continue;
                }

                $category = $categories[$catId] ?? null;
                if (!$category) {
                    continue;
                }

                $pricePerLiter = floatval($category->current_price);
                $totalPrice    = $soldLiters * $pricePerLiter;

                // بررسی کۆگا ڕاستەوخۆ لە DB
                $currentStock = floatval(
                    DB::table('categories')->where('id', $catId)->value('stock_liters') ?? 0
                );

                if ($currentStock < $soldLiters) {
                    throw new \Exception(
                        "بڕی پێویست لە کۆگای {$category->name}دا نییە. " .
                        "ماوە: {$currentStock}L، پێویستە: {$soldLiters}L"
                    );
                }

                // *** تەنها یەک جار کەم دکرێتەوە ***
                DB::table('categories')
                    ->where('id', $catId)
                    ->decrement('stock_liters', $soldLiters);

                Log::info("فرۆشتن QuickSale#{$this->id} — {$category->name}: -{$soldLiters}L");

                $this->recordSaleAndCash($totalPrice, $category, $soldLiters, 'quick_sale');

                $results['sold_details'][]     = [
                    'category_name'   => $category->name,
                    'liters'          => $soldLiters,
                    'price_per_liter' => $pricePerLiter,
                    'total_price'     => $totalPrice,
                ];
                $results['total_sold_amount'] += $totalPrice;
                $results['total_sold_liters'] += $soldLiters;
                $results['details'][]          = [
                    'type'          => 'sold',
                    'category_id'   => $catId,
                    'category_name' => $category->name,
                    'liters'        => $soldLiters,
                    'total_price'   => $totalPrice,
                    'message'       =>
                        "✅ {$soldLiters} لیتر {$category->name} فرۆشرا بە " .
                        number_format($totalPrice) . " دینار",
                ];
            }

            // گام ٣: جیاوازیەکان
            foreach ($differences as $catId => $diff) {
                if (abs($diff) < 0.01) {
                    continue;
                }

                $category = $categories[$catId] ?? null;
                if (!$category) {
                    continue;
                }

                $pricePerLiter = floatval($category->current_price);
                $totalPrice    = abs($diff) * $pricePerLiter;

                if ($diff > 0) {
                    $currentStock = floatval(
                        DB::table('categories')->where('id', $catId)->value('stock_liters') ?? 0
                    );

                    if ($currentStock < $diff) {
                        throw new \Exception(
                            "بڕی پێویست لە کۆگای {$category->name}دا نییە بۆ جیاوازی. " .
                            "ماوە: {$currentStock}L، پێویستە: {$diff}L"
                        );
                    }

                    DB::table('categories')
                        ->where('id', $catId)
                        ->decrement('stock_liters', floatval($diff));

                    $this->recordSaleAndCash($totalPrice, $category, $diff, 'quick_sale_difference');

                    $results['positive'][]             = ['category' => $category->name, 'liters' => $diff, 'price' => $totalPrice];
                    $results['total_positive_amount'] += $totalPrice;
                    $results['total_positive_liters'] += $diff;
                    $results['details'][]              = [
                        'type'          => 'positive',
                        'category_id'   => $catId,
                        'category_name' => $category->name,
                        'liters'        => $diff,
                        'total_price'   => $totalPrice,
                        'message'       => "⚕ جیاوازی: {$diff}L {$category->name} — " . number_format($totalPrice) . " دینار",
                    ];

                } else {
                    $absLiters = abs($diff);
                    $results['negative'][]             = ['category' => $category->name, 'liters' => $absLiters];
                    $results['total_negative_amount'] += $totalPrice;
                    $results['total_negative_liters'] += $absLiters;
                    $results['details'][]              = [
                        'type'          => 'negative',
                        'category_id'   => $catId,
                        'category_name' => $category->name,
                        'liters'        => $absLiters,
                        'total_price'   => $totalPrice,
                        'message'       => "⚠️ {$category->name}: {$absLiters}L کەمتر فرۆشراوە، لە کۆگا دەمێنێتەوە",
                    ];
                }
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

    protected function recordSaleAndCash(
        float $amount,
        Category $category,
        float $liters,
        string $transactionType = 'quick_sale'
    ): void {
        $cash = Cash::first() ?? Cash::create([
            'balance' => 0, 'total_income' => 0, 'total_expense' => 0,
            'capital' => 0, 'profit' => 0, 'last_update' => now(),
        ]);

        $balanceBefore      = floatval($cash->balance);
        $cash->balance      = $balanceBefore + $amount;
        $cash->total_income = floatval($cash->total_income) + $amount;
        $cash->last_update  = now();
        $cash->save();

        $description = $transactionType === 'quick_sale_difference'
            ? "جیاوازی فرۆشتن - {$liters}L {$category->name} - {$this->shift_name}"
            : "فرۆشتن - {$liters}L {$category->name} - {$this->shift_name}";

        $transaction = Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type'               => $transactionType,
            'amount'             => $amount,
            'balance_before'     => $balanceBefore,
            'balance_after'      => $cash->balance,
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

    protected function generateResultMessage(array $results): string
    {
        $lines = [];

        if ($results['total_sold_liters'] > 0) {
            $lines[] = "✅ فرۆشتنی گشتی: " .
                number_format($results['total_sold_liters']) . " لیتر - " .
                number_format($results['total_sold_amount']) . " دینار زیاد کرا بۆ قاسە";
        }

        foreach ($results['details'] as $detail) {
            if ($detail['type'] === 'sold') {
                $lines[] = "   ✅ {$detail['liters']}L {$detail['category_name']} — " .
                    number_format($detail['total_price']) . " دینار";
            }
        }

        if ($results['total_positive_liters'] > 0) {
            $lines[] = "⚕ جیاوازی زیادە: " .
                number_format($results['total_positive_liters']) . "L — " .
                number_format($results['total_positive_amount']) . " دینار";
        }

        if ($results['total_negative_liters'] > 0) {
            $lines[] = "⚠️ کەمی فرۆشراو: " .
                number_format($results['total_negative_liters']) . "L لە کۆگا دەمێنێتەوە";
        }

        return implode("\n", $lines);
    }
}
