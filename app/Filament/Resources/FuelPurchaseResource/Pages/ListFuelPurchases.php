<?php

namespace App\Filament\Resources\FuelPurchaseResource\Pages;

use App\Filament\Resources\FuelPurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFuelPurchases extends ListRecords
{
    protected static string $resource = FuelPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
