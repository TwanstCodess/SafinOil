<?php
// app/Filament/Resources/TransactionResource/Pages/ViewTransaction.php
namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('گەڕانەوە')
                ->url(fn (): string => TransactionResource::getUrl('index'))
                ->icon('heroicon-m-arrow-right')
                ->color('gray'),
        ];
    }
}
