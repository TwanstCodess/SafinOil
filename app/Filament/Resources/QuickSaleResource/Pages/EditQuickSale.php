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
        if (empty($data['sale_date'])) {
            $data['sale_date'] = Carbon::now()->format('Y-m-d');
        }

        $totalAmount = 0;
        $totalLiters = 0;

        foreach (Category::all() as $category) {
            $catId    = $category->id;
            $initial  = floatval($data['initial_readings'][$catId] ?? 0);
            $final    = floatval($data['final_readings'][$catId]   ?? 0);
            $fullDiff = $initial - $final;

            // ✅ لیتر وەک خۆی (بەبێ ÷2)
            $soldLiters = $fullDiff;

            // ✅ پارە × 2 (تەواوی پارەی هەردوو لا)
            $soldAmount = $fullDiff * floatval($category->current_price);

            $totalAmount += $soldAmount;
            $totalLiters += $soldLiters;
        }

        $data['total_amount'] = $totalAmount; // ✅ *2
        $data['total_liters'] = \$totalLiters; // ✅ لیتری تەواو (بەبێ ÷2)

        return $data;
    }

    protected function afterSave(): void
    {
        try {
            $result = $this->record->applyDifferencesToStockAndCash();

            if ($this->record->shift === 'morning' && $this->record->status === 'closed') {
                $eveningShift = QuickSale::whereDate('sale_date', $this->record->sale_date)
                    ->where('shift', 'evening')
                    ->first();

                if ($eveningShift && $eveningShift->status === 'open') {
                    $eveningShift->initial_readings = $this->record->final_readings;
                    $eveningShift->saveQuietly();

                    Log::info("شەفتی ئێوارە initial_readings نوێ کرایەوە لە بەیانی");

                    Notification::make()
                        ->info()
                        ->title('شەفتی ئێوارە نوێ کرایەوە')
                        ->body('خوێندنەوەی سەرەتایی شەفتی ئێوارە نوێ کرایەوە')
                        ->send();
                }
            }

            $this->showNotification($result);

        } catch (\Exception $e) {
            Log::error('Error in EditQuickSale afterSave: ' . $e->getMessage());

            Notification::make()
                ->title('هەڵە ڕوویدا')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function showNotification(array $result): void
    {
        if (!empty($result['applied']) && !empty($result['message'])) {
            Notification::make()
                ->title('فرۆشی خێرا بە سەرکەوتوویی نوێ کرایەوە')
                ->success()
                ->body($result['message'])
                ->persistent()
                ->send();
        } else {
            $body = !empty($result['error'])
                ? $result['error']
                : 'کۆی گشتی: ' . number_format($this->record->total_amount) .
                  ' دینار - ' . number_format($this->record->total_liters) . ' لیتر';

            Notification::make()
                ->title('فرۆشی خێرا نوێ کرایەوە')
                ->success()
                ->body($body)
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
