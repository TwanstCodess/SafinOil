<?php
// app/Filament/Resources/CashResource/Pages/ViewCash.php
namespace App\Filament\Resources\CashResource\Pages;

use App\Filament\Resources\CashResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCash extends ViewRecord
{
    protected static string $resource = CashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('دەستکاری')
                ->icon('heroicon-m-pencil'),
            Actions\Action::make('back')
                ->label('گەڕانەوە')
                ->url(fn (): string => CashResource::getUrl('index'))
                ->icon('heroicon-m-arrow-right')
                ->color('gray'),
        ];
    }
}
