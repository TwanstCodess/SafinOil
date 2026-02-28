<?php

namespace App\Filament\Resources\MoneyHistoryResource\Pages;

use App\Filament\Resources\MoneyHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMoneyHistories extends ListRecords
{
    protected static string $resource = MoneyHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
