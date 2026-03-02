<?php
// app/Filament/Resources/QuickSaleResource/Pages/CreateQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\QuickSale;
use App\Models\Category;
use Filament\Notifications\Notification;

class CreateQuickSale extends CreateRecord
{
    protected static string $resource = QuickSaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = 'open';

        // حسابکردنی total_liters و total_amount پێش تۆمارکردن
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

        // ئەگەر شەفتی ئێوارە بێت، خوێندنەوەی سەرەتایی لە کۆتایی شەفتی بەیانی وەردەگرێت
        if ($data['shift'] === 'evening') {
            $morningFinal = QuickSale::getMorningFinalReadings($data['sale_date']);

            if (!empty($morningFinal)) {
                $data['initial_readings'] = $morningFinal;

                Notification::make()
                    ->info()
                    ->title('خوێندنەوەی سەرەتایی')
                    ->body('خوێندنەوەی سەرەتایی لە کۆتایی شەفتی بەیانی وەرگیرا')
                    ->send();
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // حسابکردنی فرۆشراوەکان و جیاوازیەکان
        $this->record->calculateSoldFromReadings();
        $this->record->calculateDifferences();

        Notification::make()
            ->title('فرۆشی خێرا بە سەرکەوتوویی تۆمارکرا')
            ->success()
            ->body('کۆی گشتی: ' . number_format($this->record->total_amount) . ' دینار - ' . number_format($this->record->total_liters) . ' لیتر')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}
