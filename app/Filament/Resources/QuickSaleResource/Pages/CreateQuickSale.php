<?php
// app/Filament/Resources/QuickSaleResource/Pages/CreateQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use App\Models\QuickSale;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuickSale extends CreateRecord
{
    protected static string $resource = QuickSaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = 'open';

        // پڕکردنەوەی categories_data
        $data['categories_data'] = QuickSale::getCategoriesGroupedByType();

        return $data;
    }

    protected function afterCreate(): void
    {
        // حسابکردنی فرۆشراوەکان و جیاوازی
        $this->record->calculateAll();
    }
}
