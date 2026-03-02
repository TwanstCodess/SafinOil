<?php
// app/Filament/Resources/QuickSaleResource/Pages/ViewQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuickSale extends ViewRecord
{
    protected static string $resource = QuickSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('دەستکاری'),
            Actions\Action::make('back')
                ->label('گەڕانەوە')
                ->url(fn (): string => QuickSaleResource::getUrl('index'))
                ->icon('heroicon-m-arrow-right')
                ->color('gray'),
        ];
    }
}
