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

class CashResource extends Resource
{
    protected static ?string $model = Cash::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'قیاسەی دارایی';
    protected static ?string $pluralModelLabel = 'قیاسەی دارایی';
    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 1;

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
                                    ->extraAttributes(['class' => 'text-primary-600 font-bold text-lg']),
                                Forms\Components\TextInput::make('capital')
                                    ->label('سەرمایە')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
                                    ->extraAttributes(['class' => 'text-success-600 font-bold text-lg']),
                                Forms\Components\TextInput::make('profit')
                                    ->label('قازانجی خاوێن')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
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
                                    ->label('کۆی داهات')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->extraAttributes(['class' => 'text-success-600']),
                                Forms\Components\TextInput::make('total_expense')
                                    ->label('کۆی خەرجی')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->extraAttributes(['class' => 'text-danger-600']),
                            ]),
                        Forms\Components\DatePicker::make('last_update')
                            ->label('دوایین نوێکردنەوە')
                            ->disabled()
                            ->extraAttributes(['class' => 'text-gray-500']),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Grid::make(2)
                        ->schema([
                            Tables\Columns\Layout\Stack::make([
                                Tables\Columns\TextColumn::make('balance')
                                    ->label('ڕەوشتی قاسە')
                                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                                    ->weight('bold')
                                    ->color('primary')
                                    ->formatStateUsing(function ($state) {
                                        return self::formatMoney($state);
                                    }),
                                Tables\Columns\TextColumn::make('last_update')
                                    ->label('دوایین نوێکردنەوە')
                                    ->formatStateUsing(function ($state) {
                                        return self::formatLastUpdate($state);
                                    })
                                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                    ->color('gray'),
                            ]),

                            Tables\Columns\Layout\Grid::make(2)
                                ->schema([
                                    Tables\Columns\Layout\Stack::make([
                                        Tables\Columns\TextColumn::make('capital')
                                            ->label('سەرمایە')
                                            ->weight('bold')
                                            ->color('success')
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoney($state);
                                            }),
                                        Tables\Columns\TextColumn::make('total_income')
                                            ->label('داهات')
                                            ->color('success')
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoney($state);
                                            }),
                                    ]),

                                    Tables\Columns\Layout\Stack::make([
                                        Tables\Columns\TextColumn::make('profit')
                                            ->label('قازانج')
                                            ->weight('bold')
                                            ->color('warning')
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoney($state);
                                            }),
                                        Tables\Columns\TextColumn::make('total_expense')
                                            ->label('خەرجی')
                                            ->color('danger')
                                            ->formatStateUsing(function ($state) {
                                                return self::formatMoney($state);
                                            }),
                                    ]),
                                ]),
                        ]),
                ])->space(3),
            ])

            ->filters([
                Filter::make('balance_range')
                    ->form([
                        Forms\Components\TextInput::make('min_balance')
                            ->label('کەمترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠،٠٠٠'),
                        Forms\Components\TextInput::make('max_balance')
                            ->label('زۆرترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١،٠٠٠،٠٠٠'),
                    ])
                    ->columnSpan(2)
                    ->columns(2),

                Filter::make('last_update_filter')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('لە ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('to_date')
                            ->label('تا ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->columnSpan(2)
                    ->columns(2),

                SelectFilter::make('profit_status')
                    ->label('ڕەوشتی قازانج')
                    ->options([
                        'profitable' => 'قازانجێکی باش',
                        'low_profit' => 'قازانجی کەم',
                        'loss' => 'زیان',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'profitable' => $query->where('profit', '>', 1000000),
                            'low_profit' => $query->whereBetween('profit', [0, 1000000]),
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
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color(Color::Green)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی سەرمایە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->minValue(1000)
                                ->autofocus(),
                            Forms\Components\Textarea::make('description')
                                ->label('تێبینی')
                                ->placeholder('نموونە: زیادکردنی سەرمایەی نوێ')
                                ->maxLength(255),
                            Forms\Components\DatePicker::make('date')
                                ->label('ڕێکەوت')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (array $data, $livewire): void {
                            $cash = Cash::first() ?? Cash::initialize(0, 0);

                            try {
                                $cash->addCapital($data['amount'], $data['description'] ?? null, $data['date']);
                                $cash->calculateProfit();

                                Notification::make()
                                    ->title('سەرمایە بە سەرکەوتوویی زیاد کرا')
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
                        ->modalHeading('زیادکردنی سەرمایە')
                        ->modalIcon('heroicon-o-banknotes'),

                    Action::make('withdraw_capital')
                        ->label('کەمکردنەوەی سەرمایە')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->color(Color::Red)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی سەرمایە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->minValue(1000),
                            Forms\Components\Textarea::make('description')
                                ->label('هۆکار')
                                ->required()
                                ->placeholder('نموونە: وەرگرتنی قازانج')
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
                                $cash->withdrawCapital($data['amount'], $data['description'], $data['date']);
                                $cash->calculateProfit();

                                Notification::make()
                                    ->title('سەرمایە بە سەرکەوتوویی کەم کرایەوە')
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
                        ->modalHeading('کەمکردنەوەی سەرمایە')
                        ->modalIcon('heroicon-o-exclamation-triangle'),
                ])
                ->label('بەڕێوەبردنی سەرمایە')
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
                            ->autofocus(),
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

                        $cash->addMoney($data['amount'], $data['description'] ?? null, $data['date']);
                        $cash->calculateProfit();

                        Notification::make()
                            ->title('پارە بە سەرکەوتوویی زیاد کرا')
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
                            ->minValue(1000),
                        Forms\Components\Textarea::make('description')
                            ->label('هۆکار')
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
                            $cash->withdrawMoney($data['amount'], $data['description'], $data['date']);
                            $cash->calculateProfit();

                            Notification::make()
                                ->title('پارە بە سەرکەوتوویی کەم کرایەوە')
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
                    ->modalIcon('heroicon-o-minus-circle'),
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
                                ->prefix('دینار'),
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
                                ->prefix('دینار'),
                            Forms\Components\Textarea::make('description')
                                ->label('هۆکار')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, Cash $record): void {
                            try {
                                $record->withdrawMoney($data['amount'], $data['description'], now());
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

            ->emptyStateActions([
                Action::make('create')
                    ->label('دروستکردنی قیاسەی دارایی')
                    ->icon('heroicon-m-plus')
                    ->url(fn (): string => static::getUrl('create')),
            ])

            ->paginated(false);
    }

    private static function formatMoney($amount)
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 2) . ' ملیۆن دینار';
        } elseif ($amount >= 1000) {
            return number_format($amount / 1000, 2) . ' هەزار دینار';
        }
        return number_format($amount) . ' دینار';
    }

    private static function formatLastUpdate($state)
    {
        if (!$state) {
            return 'هەرگیز نوێ نەکراوەتەوە';
        }

        $now = now();
        $diffInDays = $now->diffInDays($state);
        $diffInHours = $now->diffInHours($state);

        if ($diffInDays > 365) {
            $years = floor($diffInDays / 365);
            return $years . ' ساڵ پێش ئێستا';
        } elseif ($diffInDays > 30) {
            $months = floor($diffInDays / 30);
            return $months . ' مانگ پێش ئێستا';
        } elseif ($diffInDays > 7) {
            $weeks = floor($diffInDays / 7);
            return $weeks . ' هەفتە پێش ئێستا';
        } elseif ($diffInDays >= 1) {
            return $diffInDays . ' ڕۆژ پێش ئێستا';
        } elseif ($diffInHours >= 1) {
            return $diffInHours . ' کاتژمێر پێش ئێستا';
        } else {
            return 'ئێستا';
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
            'edit' => Pages\EditCash::route('/{record}/edit'),
        ];
    }
}
