<?php
// app/Filament/Resources/QuickSaleResource/Pages/ListQuickSales.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuickSales extends ListRecords
{
    protected static string $resource = QuickSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('فرۆشی خێرای نوێ')
                ->icon('heroicon-m-plus'),
        ];
    }
}
