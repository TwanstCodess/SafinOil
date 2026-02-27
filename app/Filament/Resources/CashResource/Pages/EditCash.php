<?php

namespace App\Filament\Resources\CashResource\Pages;

use App\Filament\Resources\CashResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCash extends EditRecord
{
    protected static string $resource = CashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
