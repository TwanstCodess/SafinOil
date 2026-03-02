<?php
// app/Filament/Resources/QuickSaleResource/Pages/EditQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditQuickSale extends EditRecord
{
    protected static string $resource = QuickSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('بینین'),
            Actions\DeleteAction::make()
                ->label('سڕینەوە'),
        ];
    }

    protected function afterSave(): void
    {
        // دوای هەر نوێکردنەوەیەک، فرۆشراوەکان حساب بکە
        $this->record->calculateSoldFromReadings();

        // reported_sold ناگۆڕدرێت مەگەر بە شێوەی دەستی
        // تەنها differences حساب بکە
        $this->record->calculateDifferences();

        Notification::make()
            ->title('فرۆشی خێرا بە سەرکەوتوویی نوێ کرایەوە')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
