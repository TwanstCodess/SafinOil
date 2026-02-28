<?php
// app/Filament/Resources/MoneyHistoryResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\MoneyHistoryResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class MoneyHistoryResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'مێژووی پارە';
    protected static ?string $pluralModelLabel = 'مێژووی پارە';
    protected static ?string $slug = 'money-history';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری مامەڵە')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_number')
                            ->label('ژمارەی مامەڵە')
                            ->disabled(),
                        Forms\Components\TextInput::make('type')
                            ->label('جۆری مامەڵە')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => self::getTypeLabel($state)),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('balance_before')
                                    ->label('ڕەوشتی قاسە (پێش)')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->formatStateUsing(fn ($state) => number_format($state)),
                                Forms\Components\TextInput::make('balance_after')
                                    ->label('ڕەوشتی قاسە (دوای)')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->formatStateUsing(fn ($state) => number_format($state)),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('وەسف')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('ڕێکەوتی مامەڵە')
                            ->disabled(),
                        Forms\Components\TextInput::make('created_by')
                            ->label('تۆمارکراو لەلایەن')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Grid::make(2)
                        ->schema([
                            Stack::make([
                                Tables\Columns\TextColumn::make('transaction_number')
                                    ->label('ژ. مامەڵە')
                                    ->badge()
                                    ->color('gray')
                                    ->size('sm'),
                                Tables\Columns\TextColumn::make('type')
                                    ->label('جۆر')
                                    ->badge()
                                    ->color(fn ($record): string => self::getTypeColor($record->type))
                                    ->formatStateUsing(fn ($record): string => self::getTypeLabel($record->type))
                                    ->size('sm'),
                            ]),

                            Tables\Columns\TextColumn::make('amount')
                                ->label('بڕی پارە')
                                ->weight('bold')
                                ->color(fn ($record): string => $record->is_income ? 'success' : 'danger')
                                ->formatStateUsing(function ($record) {
                                    $prefix = $record->is_income ? '+' : '-';
                                    return $prefix . ' ' . number_format($record->amount) . ' دینار';
                                })
                                ->size('lg'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Tables\Columns\TextColumn::make('balance_before')
                                ->label('پێش')
                                ->formatStateUsing(fn ($state) => number_format($state) . ' دینار')
                                ->size('xs')
                                ->color('gray'),
                            Tables\Columns\TextColumn::make('balance_after')
                                ->label('دوای')
                                ->formatStateUsing(fn ($state) => number_format($state) . ' دینار')
                                ->size('xs')
                                ->color('gray'),
                        ]),

                    Tables\Columns\TextColumn::make('description')
                        ->label('وەسف')
                        ->wrap()
                        ->limit(50)
                        ->tooltip(fn ($record): string => $record->description ?? '')
                        ->size('sm'),

                    Grid::make(2)
                        ->schema([
                            Tables\Columns\TextColumn::make('transaction_date')
                                ->label('ڕێکەوت')
                                ->date('Y/m/d')
                                ->icon('heroicon-m-calendar')
                                ->size('xs'),
                            Tables\Columns\TextColumn::make('created_by')
                                ->label('لەلایەن')
                                ->icon('heroicon-m-user')
                                ->size('xs'),
                        ]),
                ])->space(2),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('جۆری مامەڵە')
                    ->options([
                        'capital_add' => 'زیادکردنی سەرمایە',
                        'capital_withdraw' => 'کەمکردنەوەی سەرمایە',
                        'cash_add' => 'زیادکردنی پارە',
                        'cash_withdraw' => 'کەمکردنەوەی پارە',
                        'sale' => 'فرۆشتن',
                        'purchase' => 'کڕین',
                        'salary' => 'مووچە',
                        'expense' => 'خەرجی',
                        'penalty' => 'سزا',
                    ])
                    ->multiple()
                    ->searchable(),

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
                            ->when($data['from_date'], fn ($q) => $q->whereDate('transaction_date', '>=', $data['from_date']))
                            ->when($data['to_date'], fn ($q) => $q->whereDate('transaction_date', '<=', $data['to_date']));
                    })
                    ->columnSpan(2)
                    ->columns(2),

                SelectFilter::make('amount_range')
                    ->label('بڕی پارە')
                    ->options([
                        'small' => 'کەم (کەمتر لە ١٠٠ هەزار)',
                        'medium' => 'مامناوەند (١٠٠ هەزار - ١ ملیۆن)',
                        'large' => 'زۆر (زیاتر لە ١ ملیۆن)',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'small' => $query->where('amount', '<', 100000),
                            'medium' => $query->whereBetween('amount', [100000, 1000000]),
                            'large' => $query->where('amount', '>', 1000000),
                            default => $query,
                        };
                    }),

                SelectFilter::make('impact')
                    ->label('کاریگەری لەسەر قاسە')
                    ->options([
                        'positive' => 'زیادکردن (داهات)',
                        'negative' => 'کەمکردن (خەرجی)',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'positive' => $query->where('is_income', true),
                            'negative' => $query->where('is_income', false),
                            default => $query,
                        };
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->persistFiltersInSession()

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('وردەکاری')
                    ->icon('heroicon-m-eye'),
            ])

            ->bulkActions([
                // هیچ bulk actionـێک نییە
            ])

            ->defaultSort('transaction_date', 'desc')
            ->poll('10s')

            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('هیچ مامەڵەیەک تۆمار نەکراوە')
            ->emptyStateDescription('کاتێک مامەڵەیەک تۆمار بکەیت، لێرەدا دەردەکەوێت');
    }

    private static function getTypeLabel($type)
    {
        return match($type) {
            'capital_add' => 'زیادکردنی سەرمایە',
            'capital_withdraw' => 'کەمکردنەوەی سەرمایە',
            'cash_add' => 'زیادکردنی پارە',
            'cash_withdraw' => 'کەمکردنەوەی پارە',
            'sale' => 'فرۆشتن',
            'purchase' => 'کڕین',
            'salary' => 'مووچە',
            'expense' => 'خەرجی',
            'penalty' => 'سزا',
            default => $type,
        };
    }

    private static function getTypeColor($type)
    {
        return match($type) {
            'capital_add', 'cash_add', 'sale' => 'success',
            'capital_withdraw', 'cash_withdraw', 'purchase', 'expense', 'penalty' => 'danger',
            'salary' => 'warning',
            default => 'gray',
        };
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
        'index' => Pages\ListMoneyHistories::route('/'),
        'view' => Pages\ViewMoneyHistory::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}


