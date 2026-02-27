<?php

namespace App\Filament\Resources\PenaltyResource\Pages;

use App\Filament\Resources\PenaltyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenalty extends EditRecord
{
    protected static string $resource = PenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
