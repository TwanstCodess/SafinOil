<?php
// app/Filament/Resources/QuickSaleResource/Pages/ListQuickSales.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Carbon\Carbon;

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

            Actions\Action::make('today_report')
                ->label('ڕاپۆرتی ئەمڕۆ')
                ->icon('heroicon-m-document-text')
                ->color('info')
                ->action(function () {
                    $date = Carbon::now()->format('Y-m-d');
                    return redirect()->to(QuickSaleResource::getUrl('index', ['tableFilters[sale_date][single_date]' => $date]));
                }),

            Actions\Action::make('tomorrow_report')
                ->label('ڕاپۆرتی سبەینێ')
                ->icon('heroicon-m-document-text')
                ->color('warning')
                ->action(function () {
                    $date = Carbon::now()->addDay()->format('Y-m-d');
                    return redirect()->to(QuickSaleResource::getUrl('index', ['tableFilters[sale_date][single_date]' => $date]));
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
                    $count = $records->count();

                    Notification::make()
                        ->title('کۆی گشتی دیاریکراوەکان')
                        ->body("ژمارە: {$count} شەفت - دینار: " . number_format($totalAmount) . ' - لیتر: ' . number_format($totalLiters))
                        ->info()
                        ->send();
                }),

            BulkAction::make('print_selected')
                ->label('چاپکردنی دیاریکراوەکان')
                ->icon('heroicon-m-printer')
                ->action(function (Collection $records) {
                    Notification::make()
                        ->title('ئەم تایبەتمەندیە لە داهاتوودا زیاد دەکرێت')
                        ->warning()
                        ->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'لیستی فرۆشی خێرا';
    }
}
