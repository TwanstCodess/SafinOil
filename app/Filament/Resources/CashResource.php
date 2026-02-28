<?php
// app/Filament/Resources/CashResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CashResource\Pages;
use App\Models\Cash;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ActionGroup;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\RawJs;

class CashResource extends Resource
{
    protected static ?string $model = Cash::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'قیاسەی دارایی';
    protected static ?string $pluralModelLabel = 'قیاسەی دارایی';
    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $slug = 'cash';

    protected static ?int $navigationSort = 1;

    /**
     * فۆرمی بینینی وردەکاری
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('پوختەی دارایی')
                    ->description('ڕەوشتی هەژماری دارایی لە ساتەی ئێستادا')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('balance')
                                            ->label('ڕەوشتی قاسە')
                                            ->money('IQD')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight('bold')
                                            ->color('primary')
                                            ->extraAttributes(['class' => 'text-2xl'])
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoneyDetailed($state);
                                            }),
                                        TextEntry::make('last_update')
                                            ->label('دوایین نوێکردنەوە')
                                            ->dateTime('Y/m/d H:i')
                                            ->since()
                                            ->color('gray'),
                                    ]),

                                Group::make()
                                    ->schema([
                                        TextEntry::make('capital')
                                            ->label('سەرمایەی سەرەتایی')
                                            ->money('IQD')
                                            ->weight('bold')
                                            ->color('success')
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoneyDetailed($state);
                                            }),
                                        TextEntry::make('total_income')
                                            ->label('کۆی گشتی داهات')
                                            ->money('IQD')
                                            ->color('success')
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoneyDetailed($state);
                                            }),
                                    ]),

                                Group::make()
                                    ->schema([
                                        TextEntry::make('profit')
                                            ->label('قازانجی خاوێن')
                                            ->money('IQD')
                                            ->weight('bold')
                                            ->color(fn ($record): string =>
                                                $record->profit >= 0 ? 'success' : 'danger'
                                            )
                                            ->formatStateUsing(function ($state) {
                                                $prefix = $state >= 0 ? '+' : '-';
                                                return $prefix . ' ' . self::formatMoneyDetailed(abs($state));
                                            }),
                                        TextEntry::make('total_expense')
                                            ->label('کۆی گشتی خەرجی')
                                            ->money('IQD')
                                            ->color('danger')
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoneyDetailed($state);
                                            }),
                                    ]),
                            ]),
                    ]),

                Section::make('ڕێژە و ڕادارە داراییەکان')
                    ->description('شیکردنەوەی دۆخی دارایی')
                    ->icon('heroicon-o-chart-pie')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('profit_margin')
                                            ->label('ڕێژەی قازانج')
                                            ->getStateUsing(function ($record) {
                                                if ($record->total_income == 0) return 0;
                                                return ($record->profit / $record->total_income) * 100;
                                            })
                                            ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                                            ->color(fn ($state) => $state >= 20 ? 'success' : ($state >= 10 ? 'warning' : 'danger'))
                                            ->icon(fn ($state) => $state >= 20 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                                            ->extraAttributes(['class' => 'text-center']),

                                        TextEntry::make('expense_ratio')
                                            ->label('ڕێژەی خەرجی')
                                            ->getStateUsing(function ($record) {
                                                if ($record->total_income == 0) return 100;
                                                return ($record->total_expense / max($record->total_income, 1)) * 100;
                                            })
                                            ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                                            ->color(fn ($state) => $state <= 70 ? 'success' : 'danger'),
                                    ]),

                                Group::make()
                                    ->schema([
                                        TextEntry::make('capital_efficiency')
                                            ->label('چالاکی سەرمایە')
                                            ->getStateUsing(function ($record) {
                                                if ($record->capital == 0) return 0;
                                                return ($record->profit / $record->capital) * 100;
                                            })
                                            ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                                            ->color(fn ($state) => $state >= 15 ? 'success' : 'warning'),

                                        TextEntry::make('roi')
                                            ->label('گەڕانەوەی وەبەرهێنان (ROI)')
                                            ->getStateUsing(function ($record) {
                                                if ($record->capital == 0) return 0;
                                                return (($record->balance - $record->capital) / $record->capital) * 100;
                                            })
                                            ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                                    ]),

                                Group::make()
                                    ->schema([
                                        IconEntry::make('is_profitable')
                                            ->label('دۆخی دارایی')
                                            ->boolean()
                                            ->trueIcon('heroicon-o-check-circle')
                                            ->falseIcon('heroicon-o-x-circle')
                                            ->trueColor('success')
                                            ->falseColor('danger')
                                            ->getStateUsing(fn ($record) => $record->profit > 0),

                                        TextEntry::make('days_operating')
                                            ->label('ماوەی کارکردن')
                                            ->getStateUsing(fn ($record) => now()->diffInDays($record->created_at) . ' ڕۆژ'),
                                    ]),

                                Group::make()
                                    ->schema([
                                        TextEntry::make('avg_daily_profit')
                                            ->label('تێکڕای قازانجی ڕۆژانە')
                                            ->getStateUsing(function ($record) {
                                                $days = max(now()->diffInDays($record->created_at), 1);
                                                return $record->profit / $days;
                                            })
                                            ->formatStateUsing(fn ($state) => self::formatMoneyDetailed($state) . '/ڕۆژ'),

                                        TextEntry::make('break_even_point')
                                            ->label('خاڵی یەکسانبوون')
                                            ->getStateUsing(function ($record) {
                                                if ($record->profit >= 0) return 'گەیشتووە';
                                                $dailyLoss = abs($record->profit) / max(now()->diffInDays($record->created_at), 1);
                                                return 'پێویستی بە ' . number_format($dailyLoss, 0) . ' دینار/ڕۆژە';
                                            })
                                            ->color(fn ($record) => $record->profit >= 0 ? 'success' : 'danger'),
                                    ]),
                            ]),
                    ]),

                Section::make('پێشبینی و ڕێنمایی')
                    ->description('ڕێنمایی بۆ باشترکردنی دۆخی دارایی')
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('advice')
                                            ->label('ڕێنمایی')
                                            ->getStateUsing(function ($record) {
                                                $advice = [];

                                                if ($record->profit < 0) {
                                                    $advice[] = '⚠️ کۆمپانیا لە زیاندا کاردەکات. پێویستە خەرجییەکان کەمبکەیتەوە.';
                                                } elseif ($record->profit < $record->total_income * 0.1) {
                                                    $advice[] = '⚠️ ڕێژەی قازانج کەمە. باشترە نرخی فرۆشتن زیاد بکەیت.';
                                                } else {
                                                    $advice[] = '✅ کۆمپانیا بە باشی کاردەکات. دەتوانیت سەرمایە زیاد بکەیت.';
                                                }

                                                if ($record->total_expense > $record->total_income * 0.7) {
                                                    $advice[] = '⚠️ خەرجییەکان زۆرن. هەوڵبدە کەمیان بکەیتەوە.';
                                                }

                                                if ($record->capital == 0) {
                                                    $advice[] = '💰 هیچ سەرمایەیەک دانەنراوە. باشترە سەرمایە دیاری بکەیت.';
                                                }

                                                return implode("\n", $advice);
                                            })
                                            ->markdown()
                                            ->extraAttributes(['class' => 'bg-gray-50 p-4 rounded-lg']),
                                    ]),

                                Group::make()
                                    ->schema([
                                        TextEntry::make('forecast')
                                            ->label('پێشبینی مانگی داهاتوو')
                                            ->getStateUsing(function ($record) {
                                                $days = max(now()->diffInDays($record->created_at), 1);
                                                $dailyProfit = $record->profit / $days;
                                                $monthlyForecast = $dailyProfit * 30;

                                                return "قازانجی پێشبینی کراو: " . self::formatMoneyDetailed($monthlyForecast);
                                            })
                                            ->extraAttributes(['class' => 'bg-blue-50 p-4 rounded-lg']),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * فۆرمی دروستکردن و دەستکاری
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری گشتی')
                    ->description('ڕەوشتی هەژماری دارایی')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('balance')
                                    ->label('ڕەوشتی قاسە')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->extraAttributes(['class' => 'text-primary-600 font-bold text-lg']),

                                Forms\Components\TextInput::make('capital')
                                    ->label('سەرمایە')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->extraAttributes(['class' => 'text-success-600 font-bold text-lg']),

                                Forms\Components\TextInput::make('profit')
                                    ->label('قازانجی خاوێن')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->extraAttributes(['class' => 'text-warning-600 font-bold text-lg']),
                            ]),
                    ]),

                Forms\Components\Section::make('پوختەی دارایی')
                    ->description('کۆی داهات و خەرجییەکان')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_income')
                                    ->label('کۆی گشتی داهات')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->extraAttributes(['class' => 'text-success-600']),

                                Forms\Components\TextInput::make('total_expense')
                                    ->label('کۆی گشتی خەرجی')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->extraAttributes(['class' => 'text-danger-600']),
                            ]),

                        Forms\Components\DatePicker::make('last_update')
                            ->label('دوایین نوێکردنەوە')
                            ->disabled()
                            ->extraAttributes(['class' => 'text-gray-500']),
                    ])->columns(2),
            ]);
    }

    /**
     * خشتەی نیشاندان
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('balance')
                            ->label('ڕەوشتی قاسە')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->weight('bold')
                            ->color('primary')
                            ->formatStateUsing(function ($state) {
                                return self::formatMoneyWithIcon($state, 'cash');
                            }),

                        Tables\Columns\TextColumn::make('last_update')
                            ->label('دوایین نوێکردنەوە')
                            ->formatStateUsing(function ($state) {
                                return self::formatLastUpdate($state);
                            })
                            ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                            ->color('gray'),
                    ]),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('capital')
                            ->label('سەرمایە')
                            ->weight('bold')
                            ->color('success')
                            ->formatStateUsing(function ($state) {
                                return self::formatMoneyWithIcon($state, 'capital');
                            }),

                        Tables\Columns\TextColumn::make('profit')
                            ->label('قازانج')
                            ->weight('bold')
                            ->color(fn ($record): string => $record->profit >= 0 ? 'success' : 'danger')
                            ->formatStateUsing(function ($state) {
                                return self::formatMoneyWithIcon($state, 'profit');
                            }),
                    ]),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('total_income')
                            ->label('داهات')
                            ->color('success')
                            ->formatStateUsing(function ($state) {
                                return self::formatMoneyWithIcon($state, 'income');
                            }),

                        Tables\Columns\TextColumn::make('total_expense')
                            ->label('خەرجی')
                            ->color('danger')
                            ->formatStateUsing(function ($state) {
                                return self::formatMoneyWithIcon($state, 'expense');
                            }),
                    ]),
                ])->from('md'),
            ])
            ->filters([
                Filter::make('balance_range')
                    ->form([
                        Forms\Components\TextInput::make('min_balance')
                            ->label('کەمترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠،٠٠٠')
                            ->mask(RawJs::make('$money($input)')),
                        Forms\Components\TextInput::make('max_balance')
                            ->label('زۆرترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١،٠٠٠،٠٠٠')
                            ->mask(RawJs::make('$money($input)')),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        $indicators = [];
                        if ($data['min_balance'] ?? null) {
                            $indicators[] = '≥ ' . number_format($data['min_balance']) . ' د.ع';
                        }
                        if ($data['max_balance'] ?? null) {
                            $indicators[] = '≤ ' . number_format($data['max_balance']) . ' د.ع';
                        }
                        return $indicators ? 'بڕی پارە: ' . implode(' و ', $indicators) : null;
                    }),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('لە ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('to_date')
                            ->label('تا ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from_date'], fn ($q) => $q->whereDate('last_update', '>=', $data['from_date']))
                            ->when($data['to_date'], fn ($q) => $q->whereDate('last_update', '<=', $data['to_date']));
                    }),

                SelectFilter::make('profit_status')
                    ->label('ڕەوشتی قازانج')
                    ->options([
                        'high_profit' => 'قازانجی زۆر (> ١ ملیۆن)',
                        'medium_profit' => 'قازانجی مامناوەند (١٠٠ هەزار - ١ ملیۆن)',
                        'low_profit' => 'قازانجی کەم (٠ - ١٠٠ هەزار)',
                        'loss' => 'زیان',
                    ])
                    ->query(function ($query, array $data) {
                        return match($data['value'] ?? null) {
                            'high_profit' => $query->where('profit', '>', 1000000),
                            'medium_profit' => $query->whereBetween('profit', [100000, 1000000]),
                            'low_profit' => $query->whereBetween('profit', [0, 100000]),
                            'loss' => $query->where('profit', '<', 0),
                            default => $query,
                        };
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->persistFiltersInSession()

            ->headerActions([
                ActionGroup::make([
                    Action::make('add_capital')
                        ->label('زیادکردنی سەرمایە')
                        ->icon('heroicon-o-banknotes')
                        ->color(Color::Green)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی سەرمایە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->minValue(1000)
                                ->mask(RawJs::make('$money($input)'))
                                ->autofocus()
                                ->helperText('ئەم بڕە وەک سەرمایەی سەرەتایی زیاد دەکرێت'),

                            Forms\Components\Textarea::make('description')
                                ->label('تێبینی')
                                ->placeholder('نموونە: زیادکردنی سەرمایەی نوێ بۆ فراوانکردنی کار')
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\DatePicker::make('date')
                                ->label('ڕێکەوتی زیادکردن')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (array $data, $livewire): void {
                            $cash = Cash::first() ?? Cash::initialize(0, 0);

                            $cash->addCapital($data['amount'], $data['description'] ?? null, $data['date']);
                            $cash->calculateProfit();

                            Notification::make()
                                ->title('سەرمایە زیاد کرا')
                                ->body(number_format($data['amount']) . ' دینار وەک سەرمایە زیاد کرا')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('زیادکردنی سەرمایە')
                        ->modalDescription('بڕی سەرمایەی نوێ داخڵ بکە')
                        ->modalIcon('heroicon-o-banknotes')
                        ->modalSubmitActionLabel('زیادکردن')
                        ->modalCancelActionLabel('پاشگەزبوونەوە'),

                    Action::make('view_report')
                        ->label('ڕاپۆرتی دارایی')
                        ->icon('heroicon-o-document-chart-bar')
                        ->color(Color::Blue)
                        ->url(fn (): string => route('filament.admin.resources.transactions.index'))
                        ->openUrlInNewTab(),

                    Action::make('export_data')
                        ->label('هەناردەکردن')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color(Color::Gray)
                        ->action(function ($livewire) {
                            // کۆدی هەناردەکردن لێرەدا
                            Notification::make()
                                ->title('ئەم تایبەتمەندییە لە بەردەستدایە')
                                ->warning()
                                ->send();
                        }),
                ])
                ->label('بەڕێوەبردنی دارایی')
                ->icon('heroicon-o-currency-dollar')
                ->color(Color::Purple)
                ->button(),

                Action::make('add_money')
                    ->label('زیادکردنی پارە')
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Orange)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->mask(RawJs::make('$money($input)'))
                            ->autofocus(),

                        Forms\Components\Select::make('source')
                            ->label('سەرچاوەی پارە')
                            ->options([
                                'bank' => 'وەرگرتن لە بانک',
                                'profit' => 'قازانجی فرۆشتن',
                                'other' => 'سەرچاوەی تر',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('تێبینی')
                            ->placeholder('نموونە: زیادکردنی پارە لە بانک')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('date')
                            ->label('ڕێکەوت')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        $cash = Cash::first() ?? Cash::initialize(0, 0);

                        $description = $data['description'] ?? '';
                        if ($data['source'] === 'bank') {
                            $description = 'وەرگرتن لە بانک - ' . $description;
                        } elseif ($data['source'] === 'profit') {
                            $description = 'قازانجی فرۆشتن - ' . $description;
                        }

                        $cash->addMoney($data['amount'], $description, $data['date']);
                        $cash->calculateProfit();

                        Notification::make()
                            ->title('پارە زیاد کرا')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('زیادکردنی پارە بۆ قاسە')
                    ->modalIcon('heroicon-o-plus-circle'),

                Action::make('withdraw_money')
                    ->label('کەمکردنەوەی پارە')
                    ->icon('heroicon-o-minus-circle')
                    ->color(Color::Red)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->mask(RawJs::make('$money($input)')),

                        Forms\Components\Select::make('reason')
                            ->label('هۆکار')
                            ->options([
                                'bank' => 'گواستنەوە بۆ بانک',
                                'withdraw' => 'وەرگرتنی قازانج',
                                'other' => 'هۆکاری تر',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('وەسف')
                            ->required()
                            ->placeholder('نموونە: گواستنەوە بۆ بانک')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('date')
                            ->label('ڕێکەوت')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        $cash = Cash::first();

                        if (!$cash) {
                            Notification::make()
                                ->title('هەڵە!')
                                ->body('قاسە بوونی نییە')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $description = $data['description'] ?? '';
                            if ($data['reason'] === 'bank') {
                                $description = 'گواستنەوە بۆ بانک - ' . $description;
                            } elseif ($data['reason'] === 'withdraw') {
                                $description = 'وەرگرتنی قازانج - ' . $description;
                            }

                            $cash->withdrawMoney($data['amount'], $description, $data['date']);
                            $cash->calculateProfit();

                            Notification::make()
                                ->title('پارە کەم کرایەوە')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('هەڵە!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('کەمکردنەوەی پارە لە قاسە')
                    ->modalIcon('heroicon-o-minus-circle')
                    ->visible(fn (): bool => optional(Cash::first())->balance > 0 ?? false),
            ])

            ->actions([
                ActionGroup::make([
                    Action::make('view_details')
                        ->label('بینینی وردەکاری')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Cash $record): string => static::getUrl('view', ['record' => $record])),

                    Action::make('quick_add')
                        ->label('زیادکردنی پارە')
                        ->icon('heroicon-m-plus')
                        ->color(Color::Orange)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی پارە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->mask(RawJs::make('$money($input)')),

                            Forms\Components\Textarea::make('description')
                                ->label('تێبینی')
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, Cash $record): void {
                            $record->addMoney($data['amount'], $data['description'] ?? null, now());
                            $record->calculateProfit();

                            Notification::make()
                                ->title('زیاد کرا')
                                ->success()
                                ->send();
                        }),

                    Action::make('quick_withdraw')
                        ->label('کەمکردنەوەی پارە')
                        ->icon('heroicon-m-minus')
                        ->color(Color::Red)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی پارە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->mask(RawJs::make('$money($input)')),

                            Forms\Components\Textarea::make('reason')
                                ->label('هۆکار')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, Cash $record): void {
                            try {
                                $record->withdrawMoney($data['amount'], $data['reason'], now());
                                $record->calculateProfit();

                                Notification::make()
                                    ->title('کەم کرایەوە')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('هەڵە!')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Cash $record): bool => $record->balance > 0),
                ])
                ->label('کردارەکان')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color(Color::Gray)
                ->size('sm'),
            ])

            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('هیچ قیاسەیەکی دارایی نییە')
            ->emptyStateDescription('یەکەم قیاسەی دارایی دروست بکە بۆ دەستپێکردنی تۆمارکردن')
            ->emptyStateActions([
                Action::make('create')
                    ->label('دروستکردنی قیاسەی دارایی')
                    ->icon('heroicon-m-plus')
                    ->url(fn (): string => static::getUrl('create')),
            ])

            ->poll('10s')
            ->paginated(false);
    }

    /**
     * فۆرمەتی پارە بە شێوازی ورد
     */
    private static function formatMoneyDetailed($amount): string
    {
        if ($amount >= 1000000) {
            $millions = floor($amount / 1000000);
            $thousands = floor(($amount % 1000000) / 1000);
            $remainder = $amount % 1000;

            $parts = [];
            if ($millions > 0) {
                $parts[] = number_format($millions) . ' ملیۆن';
            }
            if ($thousands > 0) {
                $parts[] = number_format($thousands) . ' هەزار';
            }
            if ($remainder > 0 && $millions == 0) {
                $parts[] = number_format($remainder);
            }

            return implode(' و ', $parts) . ' دینار';
        } elseif ($amount >= 1000) {
            $thousands = floor($amount / 1000);
            $remainder = $amount % 1000;

            if ($remainder > 0) {
                return number_format($thousands) . ' هەزار و ' . number_format($remainder) . ' دینار';
            }
            return number_format($thousands) . ' هەزار دینار';
        }

        return number_format($amount) . ' دینار';
    }

    /**
     * فۆرمەتی پارە بە ئایکۆن
     */
    private static function formatMoneyWithIcon($amount, $type): string
    {
        $icon = match($type) {
            'cash' => '💰',
            'capital' => '🏦',
            'profit' => '📈',
            'income' => '💵',
            'expense' => '💸',
            default => ''
        };

        $formatted = self::formatMoneyDetailed($amount);
        return $icon . ' ' . $formatted;
    }

    /**
     * فۆرمەتی دوایین نوێکردنەوە
     */
    private static function formatLastUpdate($state): string
    {
        if (!$state) {
            return '⏳ هەرگیز نوێ نەکراوەتەوە';
        }

        $now = now();
        $diffInDays = $now->diffInDays($state);
        $diffInHours = $now->diffInHours($state);

        if ($diffInDays > 365) {
            $years = floor($diffInDays / 365);
            return "⏱️ {$years} ساڵ پێش ئێستا";
        } elseif ($diffInDays > 30) {
            $months = floor($diffInDays / 30);
            return "⏱️ {$months} مانگ پێش ئێستا";
        } elseif ($diffInDays > 7) {
            $weeks = floor($diffInDays / 7);
            return "⏱️ {$weeks} هەفتە پێش ئێستا";
        } elseif ($diffInDays >= 1) {
            return "⏱️ {$diffInDays} ڕۆژ پێش ئێستا";
        } elseif ($diffInHours >= 1) {
            return "⏱️ {$diffInHours} کاتژمێر پێش ئێستا";
        } else {
            return "🟢 ئێستا";
        }
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashes::route('/'),
            'create' => Pages\CreateCash::route('/create'),
            'view' => Pages\ViewCash::route('/{record}'),
            'edit' => Pages\EditCash::route('/{record}/edit'),
        ];
    }

    /**
     * نیشاندانی بەج لە ناڤیگەیشن
     */
    public static function getNavigationBadge(): ?string
    {
        $cash = Cash::first();
        return $cash ? self::formatMoneySimple($cash->balance) : '0 دینار';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $cash = Cash::first();
        if (!$cash) return 'gray';

        if ($cash->profit > 1000000) return 'success';
        if ($cash->profit > 0) return 'warning';
        if ($cash->profit < 0) return 'danger';

        return 'gray';
    }

    private static function formatMoneySimple($amount): string
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 1) . 'M';
        } elseif ($amount >= 1000) {
            return number_format($amount / 1000, 1) . 'K';
        }
        return number_format($amount);
    }
}
