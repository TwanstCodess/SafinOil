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
        $this->record->calculateAll();

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
