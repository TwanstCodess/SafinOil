<?php
// app/Filament/Resources/QuickSaleResource/Pages/CreateQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\QuickSale;
use App\Models\Category;
use Filament\Notifications\Notification;
class CreateQuickSale extends CreateRecord
{
    protected static string $resource = QuickSaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = 'open';

        // پڕکردنەوەی reported_sold بە دیفۆڵت
        if (!isset($data['reported_sold'])) {
            $data['reported_sold'] = [];
            $categories = Category::all();
            foreach ($categories as $category) {
                $data['reported_sold'][$category->id] = 0;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->calculateAll();

        Notification::make()
            ->title('فرۆشی خێرا بە سەرکەوتوویی تۆمارکرا')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
