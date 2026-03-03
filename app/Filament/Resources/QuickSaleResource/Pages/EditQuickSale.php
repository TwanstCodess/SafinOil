<?php
// app/Filament/Resources/QuickSaleResource/Pages/EditQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use App\Models\QuickSale;
use App\Models\Category;
use App\Models\Cash;
use App\Models\Transaction;
use App\Models\Sale;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord; // دڵنیابە لەمە
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditQuickSale extends EditRecord  // دڵنیابە کە EditRecordـە
{
    protected static string $resource = QuickSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('بینین')
                ->icon('heroicon-m-eye'),
            Actions\DeleteAction::make()
                ->label('سڕینەوە')
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // دیاریکردنی بەروار ئەگەر دیاری نەکرابێت
        if (!isset($data['sale_date']) || empty($data['sale_date'])) {
            $data['sale_date'] = Carbon::now()->format('Y-m-d');
        }

        // دووبارە حسابکردنی total_liters و total_amount
        $totalAmount = 0;
        $totalLiters = 0;
        $categories = Category::all();

        foreach ($categories as $category) {
            $catId = $category->id;
            $initial = floatval($data['initial_readings'][$catId] ?? 0);
            $final = floatval($data['final_readings'][$catId] ?? 0);
            $sold = $initial - $final;

            $totalAmount += $sold * $category->current_price;
            $totalLiters += $sold;
        }

        $data['total_amount'] = $totalAmount;
        $data['total_liters'] = $totalLiters;

        return $data;
    }

    protected function afterSave(): void
    {
        try {
            DB::beginTransaction();

            // دووبارە حسابکردنی هەموو شتەکان
            $this->record->calculateSoldFromReadings();
            $differences = $this->record->calculateDifferences();

            // گەڕان بەدوای جیاوازیەکاندا (ئەو بڕانەی کە زیادیان کردووە یان کەمیان کردووە)
            $this->handleDifferences($differences);

            // ئەگەر ئەم شەفتە بەیانی بێت و داخرابێت، شەفتی ئێوارەی هەمان ڕۆژ دەستکاری بکە
            if ($this->record->shift === 'morning' && $this->record->status === 'closed') {
                $eveningShift = QuickSale::whereDate('sale_date', $this->record->sale_date)
                    ->where('shift', 'evening')
                    ->first();

                if ($eveningShift && $eveningShift->status === 'open') {
                    $eveningShift->update([
                        'initial_readings' => $this->record->final_readings
                    ]);

                    // دووبارە حسابکردنی شەفتی ئێوارە
                    $eveningShift->calculateSoldFromReadings();
                    $eveningShift->calculateDifferences();

                    Notification::make()
                        ->info()
                        ->title('شەفتی ئێوارە نوێ کرایەوە')
                        ->body('خوێندنەوەی سەرەتایی شەفتی ئێوارە نوێ کرایەوە')
                        ->send();
                }
            }

            DB::commit();

            // نیشاندانی ئاگاداری
            $this->showDifferenceNotification($differences);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in EditQuickSale afterSave: ' . $e->getMessage());

            Notification::make()
                ->title('هەڵە ڕوویدا')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * مامەڵەکردن لەگەڵ جیاوازیەکان
     */
    protected function handleDifferences($differences)
    {
        if (empty($differences)) {
            return;
        }

        $categories = Category::all()->keyBy('id');

        foreach ($differences as $catId => $diff) {
            if (abs($diff) < 0.01) continue; // نەگرتنی بڕە زۆر بچووک

            $category = $categories[$catId] ?? null;
            if (!$category) continue;

            if ($diff > 0) {
                // بڕی فرۆشراوی تۆ زیاترە لە بڕی ڕاستەقینە
                // => پارە زیاد دەکەین بۆ قاسە (وەک فرۆشتن)
                $this->handlePositiveDifference($category, $diff);
            } else {
                // بڕی فرۆشراوی تۆ کەمترە لە بڕی ڕاستەقینە
                // => بڕەکە لە کۆگا دەمێنێتەوە، پارەکە ناچێتە قاسە
                $this->handleNegativeDifference($category, abs($diff));
            }
        }
    }

    /**
     * مامەڵەکردن لەگەڵ جیاوازی ئەرێنی (فرۆشراوی تۆ زیاترە)
     * واتە کڕیار پارەی زیادەی داوە
     */
    protected function handlePositiveDifference($category, $diffLiters)
    {
        $pricePerLiter = $category->current_price;
        $totalPrice = $diffLiters * $pricePerLiter;

        // ١. کەمکردنەوەی بڕەکە لە کۆگا
        $category->updateStock($diffLiters, 'subtract');

        // ٢. زیادکردنی پارە بۆ قاسە
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
        $cash->balance += $totalPrice;
        $cash->total_income += $totalPrice;
        $cash->last_update = now();
        $cash->save();

        // ٣. تۆمارکردنی مامەڵە لە Transaction
        $transaction = Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'sale',
            'amount' => $totalPrice,
            'balance_before' => $balanceBefore,
            'balance_after' => $cash->balance,
            'reference_number' => $this->record->id,
            'description' => "فرۆشتنی زیادە - {$diffLiters} لیتر {$category->name} - شەفتی {$this->record->shift_name}",
            'transaction_date' => $this->record->sale_date,
            'created_by' => auth()->user()?->name ?? 'سیستەم',
        ]);

        // ٤. تۆمارکردنی فرۆشتن لە Sale (بۆ ڕاپۆرت)
        $sale = Sale::create([
            'category_id' => $category->id,
            'liters' => $diffLiters,
            'price_per_liter' => $pricePerLiter,
            'total_price' => $totalPrice,
            'sale_date' => $this->record->sale_date,
            'payment_type' => 'cash',
            'status' => 'paid',
            'paid_amount' => $totalPrice,
            'remaining_amount' => 0,
            'notes' => "فرۆشتنی زیادە لە شەفتی {$this->record->shift_name}",
        ]);

        // ٥. پەیوەستکردنی Transaction بە Sale
        $transaction->transactionable_type = Sale::class;
        $transaction->transactionable_id = $sale->id;
        $transaction->save();

        Log::info("زیادە فرۆشرا: {$diffLiters} لیتر {$category->name} - {$totalPrice} دینار");
    }

    /**
     * مامەڵەکردن لەگەڵ جیاوازی نەرێنی (فرۆشراوی تۆ کەمترە)
     * واتە بڕەکە لە کۆگا دەمێنێتەوە
     */
    protected function handleNegativeDifference($category, $diffLiters)
    {
        // هیچ کارێک ناکەین، بڕەکە لە کۆگا دەمێنێتەوە
        // چونکە فرۆشراوی تۆ کەمتر بووە لە بڕی ڕاستەقینە

        Log::info("کەمتر فرۆشرا: {$diffLiters} لیتر {$category->name} - لە کۆگا دەمێنێتەوە");
    }

    /**
     * نیشاندانی ئاگاداری جیاوازیەکان
     */
    protected function showDifferenceNotification($differences)
    {
        if (empty($differences)) {
            Notification::make()
                ->title('فرۆشی خێرا بە سەرکەوتوویی نوێ کرایەوە')
                ->success()
                ->body('کۆی گشتی: ' . number_format($this->record->total_amount) . ' دینار - ' . number_format($this->record->total_liters) . ' لیتر')
                ->send();
            return;
        }

        $positiveTotal = 0;
        $negativeTotal = 0;
        $categories = Category::all()->keyBy('id');

        foreach ($differences as $catId => $diff) {
            if (abs($diff) < 0.01) continue;

            $category = $categories[$catId] ?? null;
            if (!$category) continue;

            $price = $diff * $category->current_price;

            if ($diff > 0) {
                $positiveTotal += $price;
            } else {
                $negativeTotal += abs($price);
            }
        }

        $message = "";

        if ($positiveTotal > 0) {
            $message .= "✅ فرۆشتنی زیادە: " . number_format($positiveTotal) . " دینار زیاد کرا بۆ قاسە\n";
        }

        if ($negativeTotal > 0) {
            $message .= "⚠️ کەمی فرۆشراو: " . number_format($negativeTotal) . " دینار لە کۆگا دەمێنێتەوە";
        }

        Notification::make()
            ->title('فرۆشی خێرا بە سەرکەوتوویی نوێ کرایەوە')
            ->success()
            ->body($message)
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }
}
