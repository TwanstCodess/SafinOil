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
                                    ->icon(fn ($record): string => $record->shift === 'morning' ? 'heroicon-m-sun' : 'heroicon-m-moon'),

                                TextEntry::make('status')
                                    ->label('ڕەوشت')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'open' => 'success',
                                        'closed' => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'open' => 'کراوە',
                                        'closed' => 'داخراو',
                                    }),

                                TextEntry::make('creator.name')
                                    ->label('تۆمارکراو لەلایەن')
                                    ->icon('heroicon-m-user')
                                    ->default('سیستەم'),
                            ]),
                    ]),

                Section::make('پوختەی فرۆشتن')
                    ->icon('heroicon-m-chart-bar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('total_amount')
                                    ->label('کۆی گشتی (دینار)')
                                    ->money('IQD')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->color('success')
                                    ->weight('bold')
                                    ->alignCenter(),

                                TextEntry::make('total_liters')
                                    ->label('کۆی گشتی (لیتر)')
                                    ->formatStateUsing(fn ($state): string => number_format($state, 0) . ' لیتر')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->color('info')
                                    ->weight('bold')
                                    ->alignCenter(),
                            ]),
                    ]),

                Tabs::make('وردەکاری فرۆشتن')
                    ->tabs([
                        Tabs\Tab::make('خوێندنەوەکان')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->schema([
                                ...$this->getReadingsViewSchema(),
                            ]),

                        Tabs\Tab::make('فرۆشراوەکان')
                            ->icon('heroicon-m-shopping-cart')
                            ->schema([
                                ...$this->getSoldViewSchema(),
                            ]),

                        Tabs\Tab::make('جیاوازیەکان')
                            ->icon('heroicon-m-scale')
                            ->schema([
                                ...$this->getDifferencesViewSchema(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function getReadingsViewSchema(): array
    {
        $schema = [];
        $categories = Category::with('type')->get();

        $grouped = [];
        foreach ($categories as $category) {
            $typeKey = $category->type->key ?? 'other';
            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'name' => $category->type->name ?? 'ئەوانی تر',
                    'items' => []
                ];
            }
            $grouped[$typeKey]['items'][] = $category;
        }

        foreach ($grouped as $group) {
            $schema[] = Section::make($group['name'])
                ->schema(function () use ($group) {
                    $fields = [];
                    foreach ($group['items'] as $category) {
                        $fields[] = Grid::make(3)
                            ->schema([
                                TextEntry::make("initial_readings.{$category->id}")
                                    ->label($category->name . ' - سەرەتایی')
                                    ->formatStateUsing(fn ($state): string => number_format(floatval($state ?? 0), 0) . ' لیتر')
                                    ->color('blue')
                                    ->inlineLabel(),

                                TextEntry::make("final_readings.{$category->id}")
                                    ->label($category->name . ' - کۆتایی')
                                    ->formatStateUsing(fn ($state): string => number_format(floatval($state ?? 0), 0) . ' لیتر')
                                    ->color('purple')
                                    ->inlineLabel(),

                                TextEntry::make("")
                                    ->label('نرخ')
                                    ->default(number_format($category->current_price) . ' د.ع')
                                    ->color('gray')
                                    ->inlineLabel(),
                            ]);
                    }
                    return $fields;
                })
                ->columns(1)
                ->collapsible();
        }

        return $schema;
    }

    private function getSoldViewSchema(): array
    {
        $schema = [];
        $categories = Category::with('type')->get();

        $totalSoldLiters = 0;
        $totalSoldAmount = 0;

        foreach ($categories as $category) {
            $initial = floatval($this->record->initial_readings[$category->id] ?? 0);
            $final = floatval($this->record->final_readings[$category->id] ?? 0);
            $sold = $initial - $final;
            $amount = $sold * $category->current_price;

            $totalSoldLiters += $sold;
            $totalSoldAmount += $amount;

            $schema[] = Grid::make(3)
                ->schema([
                    TextEntry::make("")
                        ->label('کاتیگۆری')
                        ->default($category->name)
                        ->weight('bold'),

                    TextEntry::make("")
                        ->label('فرۆشراو (لیتر)')
                        ->default(number_format($sold, 0) . ' لیتر')
                        ->color($sold > 0 ? 'success' : 'gray'),

                    TextEntry::make("")
                        ->label('کۆی (دینار)')
                        ->default(number_format($amount) . ' د.ع')
                        ->color($amount > 0 ? 'success' : 'gray'),
                ]);
        }

        // کۆی گشتی
        $schema[] = Section::make('کۆی گشتی')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextEntry::make("")
                            ->label('کۆی گشتی لیتر')
                            ->default(number_format($totalSoldLiters, 0) . ' لیتر')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->color('info')
                            ->weight('bold')
                            ->alignCenter(),

                        TextEntry::make("")
                            ->label('کۆی گشتی دینار')
                            ->default(number_format($totalSoldAmount) . ' د.ع')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->color('success')
                            ->weight('bold')
                            ->alignCenter(),
                    ]),
            ]);

        return $schema;
    }

    private function getDifferencesViewSchema(): array
    {
        $schema = [];
        $categories = Category::with('type')->get();

        $totalDiffLiters = 0;
        $totalDiffAmount = 0;

        foreach ($categories as $category) {
            $initial = floatval($this->record->initial_readings[$category->id] ?? 0);
            $final = floatval($this->record->final_readings[$category->id] ?? 0);
            $sold = $initial - $final;
            $reported = floatval($this->record->reported_sold[$category->id] ?? $sold);
            $diffLiters = $reported - $sold;
            $diffAmount = $diffLiters * $category->current_price;

            $totalDiffLiters += $diffLiters;
            $totalDiffAmount += $diffAmount;

            $diffColor = $diffLiters == 0 ? 'gray' : ($diffLiters > 0 ? 'success' : 'danger');
            $icon = $diffLiters == 0 ? '✓' : ($diffLiters > 0 ? '↑' : '↓');

            $schema[] = Grid::make(4)
                ->schema([
                    TextEntry::make("")
                        ->label('کاتیگۆری')
                        ->default($category->name)
                        ->weight('bold'),

                    TextEntry::make("")
                        ->label('فرۆشراو (لیتر)')
                        ->default(number_format($sold, 0) . ' لیتر')
                        ->color('primary'),

                    TextEntry::make("")
                        ->label('فرۆشراوی تۆ (لیتر)')
                        ->default(number_format($reported, 0) . ' لیتر')
                        ->color('info'),

                    TextEntry::make("")
                        ->label('جیاوازی')
                        ->default(new HtmlString("
                            <div class='flex items-center gap-1'>
                                <span class='text-{$diffColor}-600 font-bold'>{$icon}</span>
                                <span class='text-{$diffColor}-600 font-bold'>" . number_format(abs($diffLiters)) . " لیتر</span>
                                <span class='text-xs text-{$diffColor}-600'>(" . number_format(abs($diffAmount)) . " د.ع)</span>
                            </div>
                        "))
                        ->html(),
                ]);
        }

        // کۆی گشتی جیاوازیەکان
        $totalDiffColor = $totalDiffLiters == 0 ? 'gray' : ($totalDiffLiters > 0 ? 'success' : 'danger');
        $totalIcon = $totalDiffLiters == 0 ? '✓' : ($totalDiffLiters > 0 ? '↑' : '↓');

        $schema[] = Section::make('کۆی گشتی جیاوازیەکان')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextEntry::make("")
                            ->label('کۆی گشتی جیاوازی (لیتر)')
                            ->default(new HtmlString("
                                <div class='flex items-center gap-2 justify-center'>
                                    <span class='text-{$totalDiffColor}-600 font-bold text-2xl'>{$totalIcon}</span>
                                    <span class='text-{$totalDiffColor}-600 font-bold text-2xl'>" . number_format(abs($totalDiffLiters)) . " لیتر</span>
                                </div>
                            "))
                            ->html()
                            ->alignCenter(),

                        TextEntry::make("")
                            ->label('کۆی گشتی جیاوازی (دینار)')
                            ->default(new HtmlString("
                                <span class='text-{$totalDiffColor}-600 font-bold text-2xl'>" . number_format(abs($totalDiffAmount)) . " د.ع</span>
                            "))
                            ->html()
                            ->alignCenter(),
                    ]),
            ]);

        return $schema;
    }

    public function getTitle(): string
    {
        return "بینینی فرۆشی خێرا - {$this->record->shift_name} - " . Carbon::parse($this->record->sale_date)->format('Y/m/d');
    }
}
