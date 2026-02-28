<?php

namespace App\Filament\Resources\CreditPaymentResource\Pages;

use App\Filament\Resources\CreditPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCreditPayment extends EditRecord
{
    protected static string $resource = CreditPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
