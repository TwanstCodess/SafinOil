<?php
// app/Filament/Resources/QuickSaleResource/Pages/EditQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use App\Models\QuickSale;
use App\Models\Category;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditQuickSale extends EditRecord
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

            // جێبەجێکردنی جیاوازیەکان بۆ ئەم شەفتە
            $result = $this->record->applyDifferencesToStockAndCash();

            // ئەگەر ئەم شەفتە بەیانی بێت و داخرابێت، شەفتی ئێوارەی هەمان ڕۆژ نوێ بکەوە
            if ($this->record->shift === 'morning' && $this->record->status === 'closed') {
                $eveningShift = QuickSale::whereDate('sale_date', $this->record->sale_date)
                    ->where('shift', 'evening')
                    ->first();

                if ($eveningShift && $eveningShift->status === 'open') {
                    // تەنها خوێندنەوەی سەرەتایی شەفتی ئێوارە نوێ بکەوە
                    $eveningShift->initial_readings = $this->record->final_readings;
                    $eveningShift->save();

                    // دووبارە حسابکردنی شەفتی ئێوارە
                    $eveningShift->applyDifferencesToStockAndCash();

                    Notification::make()
                        ->info()
                        ->title('شەفتی ئێوارە نوێ کرایەوە')
                        ->body('خوێندنەوەی سەرەتایی شەفتی ئێوارە نوێ کرایەوە')
                        ->send();
                }
            }

            DB::commit();

            // نیشاندانی ئاگاداری
            $this->showNotification($result);

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
     * نیشاندانی ئاگاداری
     */
    protected function showNotification($result)
    {
        if ($result['applied'] && isset($result['message'])) {
            Notification::make()
                ->title('فرۆشی خێرا بە سەرکەوتوویی نوێ کرایەوە')
                ->success()
                ->body($result['message'])
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('فرۆشی خێرا بە سەرکەوتوویی نوێ کرایەوە')
                ->success()
                ->body('کۆی گشتی: ' . number_format($this->record->total_amount) . ' دینار - ' . number_format($this->record->total_liters) . ' لیتر')
                ->send();
        }
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
