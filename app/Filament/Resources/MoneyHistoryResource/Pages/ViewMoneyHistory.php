<?php
// app/Filament/Resources/MoneyHistoryResource/Pages/ViewMoneyHistory.php
namespace App\Filament\Resources\MoneyHistoryResource\Pages;

use App\Filament\Resources\MoneyHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMoneyHistory extends ViewRecord
{
    protected static string $resource = MoneyHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('گەڕانەوە بۆ لیست')
                ->url(fn (): string => MoneyHistoryResource::getUrl('index'))
                ->icon('heroicon-m-arrow-right')
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // نابێت دەستکاری بکرێت
        return $data;
    }
}
