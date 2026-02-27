<?php

namespace App\Filament\Resources\FuelPurchaseResource\Pages;

use App\Filament\Resources\FuelPurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFuelPurchase extends CreateRecord
{
    protected static string $resource = FuelPurchaseResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
