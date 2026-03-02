<?php
// app/Filament/Resources/QuickSaleResource/Pages/EditQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use App\Models\QuickSale;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
        $this->record->calculateSoldFromReadings();
        $this->record->calculateDifferences();

        // ئەگەر ئەم شەفتە بەیانی بێت و داخرابێت، شەفتی ئێوارەی هەمان ڕۆژ دەستکاری بکە
        if ($this->record->shift === 'morning' && $this->record->status === 'closed') {
            $eveningShift = QuickSale::whereDate('sale_date', $this->record->sale_date)
                ->where('shift', 'evening')
                ->first();

            if ($eveningShift && $eveningShift->status === 'open') {
                $eveningShift->update([
                    'initial_readings' => $this->record->final_readings
                ]);

                Notification::make()
                    ->info()
                    ->title('شەفتی ئێوارە نوێ کرایەوە')
                    ->body('خوێندنەوەی سەرەتایی شەفتی ئێوارە نوێ کرایەوە')
                    ->send();
            }
        }

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
