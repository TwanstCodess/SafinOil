<?php
// app/Filament/Resources/QuickSaleResource/Pages/ViewQuickSale.php
namespace App\Filament\Resources\QuickSaleResource\Pages;

use App\Filament\Resources\QuickSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Illuminate\Support\HtmlString;
use App\Models\Category;
use Carbon\Carbon;

class ViewQuickSale extends ViewRecord
{
    protected static string $resource = QuickSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('دەستکاری')
                ->icon('heroicon-m-pencil')
                ->color('warning'),

            Actions\Action::make('back')
                ->label('گەڕانەوە بۆ لیست')
                ->url(fn (): string => QuickSaleResource::getUrl('index'))
                ->icon('heroicon-m-arrow-right')
                ->color('gray'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('زانیاری گشتی')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('sale_date')
                                    ->label('ڕێکەوت')
                                    ->date('Y/m/d')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('Y/m/d')),

                                TextEntry::make('shift_name')
                                    ->label('شەفت')
                                    ->badge()
                                    ->color(fn ($record): string => $record->shift_color)
                                    ->icon(fn ($record): string =>
