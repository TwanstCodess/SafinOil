<?php
// app/Filament/Resources/QuickSaleResource/Pages/ListQuickSales.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class ListQuickSales extends ListRecords
{
    protected static string $resource = QuickSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('فرۆشی خێرای نوێ')
                ->icon('heroicon-m-plus')
                ->color('success'),

            Actions\Action::make('export')
                ->label('هەناردەکردن')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    Notification::make()
                        ->title('ئەم تایبەتمەندیە لە داهاتوودا زیاد دەکرێت')
                        ->warning()
                        ->send();
                }),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('export_selected')
                ->label('هەناردەکردنی دیاریکراوەکان')
                ->icon('heroicon-m-arrow-down-tray')
                ->action(function (Collection $records) {
                    $totalAmount = $records->sum('total_amount');
                    $totalLiters = $records->sum('total_liters');

                    Notification::make()
                        ->title('کۆی گشتی دیاریکراوەکان')
                        ->body('دینار: ' . number_format($totalAmount) . ' - لیتر: ' . number_format($totalLiters))
                        ->info()
                        ->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'لیستی فرۆشی خێرا';
    }
}
