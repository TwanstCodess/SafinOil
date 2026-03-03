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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

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
                                    ->size('sm')
                                    ->searchable(),
                                Tables\Columns\TextColumn::make('type')
                                    ->label('جۆر')
                                    ->badge()
                                    ->color(fn ($record): string => self::getTypeColor($record->type))
                                    ->formatStateUsing(fn ($record): string => self::getTypeLabel($record->type))
                                    ->size('sm')
                                    ->searchable(),
                            ]),

                            // *** بڕی پارە ***
                            // ئەگەر جۆری quick_sale_difference بوو، بڕەکە × 2 نیشان دەدرێت
                            Tables\Columns\TextColumn::make('amount')
                                ->label('بڕی پارە')
                                ->weight('bold')
                                ->color(fn ($record): string => $record->is_income ? 'success' : 'danger')
                                ->formatStateUsing(function ($record) {
                                    $amount = $record->type === 'quick_sale_difference'
                                        ? floatval($record->amount) * 2
                                        : floatval($record->amount);

                                    $prefix = $record->is_income ? '+' : '-';
                                    return $prefix . ' ' . number_format($amount) . ' دینار';
                                })
                                ->size('lg')
                                ->sortable(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Tables\Columns\TextColumn::make('balance_before')
                                ->label('پێش')
                                ->formatStateUsing(fn ($state) => number_format($state) . ' دینار')
                                ->size('xs')
                                ->color('gray')
                                ->toggleable(isToggledHiddenByDefault: true),
                            Tables\Columns\TextColumn::make('balance_after')
                                ->label('دوای')
                                ->formatStateUsing(fn ($state) => number_format($state) . ' دینار')
                                ->size('xs')
                                ->color('gray')
                                ->toggleable(isToggledHiddenByDefault: true),
                        ]),

                    Tables\Columns\TextColumn::make('description')
                        ->label('وەسف')
                        ->wrap()
                        ->limit(50)
                        ->tooltip(fn ($record): string => $record->description ?? '')
                        ->size('sm')
                        ->searchable(),

                    Grid::make(2)
                        ->schema([
                            Tables\Columns\TextColumn::make('transaction_date')
                                ->label('ڕێکەوت')
                                ->date('Y/m/d')
                                ->icon('heroicon-m-calendar')
                                ->size('xs')
                                ->sortable(),
                            Tables\Columns\TextColumn::make('created_by')
                                ->label('لەلایەن')
                                ->icon('heroicon-m-user')
                                ->size('xs')
                                ->searchable(),
                        ]),
                ])->space(2),
            ])

            ->filters([
                // فلتەری جۆری مامەڵە — quick_sale و quick_sale_difference زیادکرا
                SelectFilter::make('type')
                    ->label('جۆری مامەڵە')
                    ->options([
                        'capital_add'           => '➕ زیادکردنی سەرمایە',
                        'capital_withdraw'       => '➖ کەمکردنەوەی سەرمایە',
                        'cash_add'              => '💰 زیادکردنی پارە',
                        'cash_withdraw'         => '💸 کەمکردنەوەی پارە',
                        'sale'                  => '🛒 فرۆشتن',
                        'quick_sale'            => '⚡ فرۆشی خێرا',
                        'quick_sale_difference' => '⚡ جیاوازی فرۆشی خێرا',
                        'purchase'              => '📦 کڕین',
                        'salary'                => '👤 مووچە',
                        'expense'               => '📉 خەرجی',
                        'penalty'               => '⚠️ سزا',
                        'credit_payment'        => '💳 دانەوەی قەرز',
                    ])
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(12)
                    ->indicator('جۆر')
                    ->placeholder('هەموو جۆرەکان')
                    ->columnSpan(2),

                // فلتەری داهات/خەرجی — quick_sale جۆرەکان زیادکرا
                SelectFilter::make('impact')
                    ->label('کاریگەری لەسەر قاسە')
                    ->options([
                        'positive' => '📈 داهات (زیادکردن)',
                        'negative' => '📉 خەرجی (کەمکردن)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return match($data['value']) {
                            'positive' => $query->whereIn('type', ['sale', 'quick_sale', 'quick_sale_difference', 'cash_add', 'capital_add', 'credit_payment']),
                            'negative' => $query->whereIn('type', ['purchase', 'expense', 'salary', 'penalty', 'cash_withdraw', 'capital_withdraw']),
                            default    => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'positive' => '📈 داهات',
                            'negative' => '📉 خەرجی',
                            default    => null,
                        };
                    })
                    ->columnSpan(1),

                Filter::make('date_range')
                    ->label('مەودای بەروار')
                    ->form([
                        DatePicker::make('from_date')->label('لە ڕێکەوتی')->placeholder('YYYY-MM-DD'),
                        DatePicker::make('to_date')->label('تا ڕێکەوتی')->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from_date'], fn ($q) => $q->whereDate('transaction_date', '>=', $data['from_date']))
                            ->when($data['to_date'],   fn ($q) => $q->whereDate('transaction_date', '<=', $data['to_date']));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['from_date'] ?? null) $indicators[] = 'لە ' . \Carbon\Carbon::parse($data['from_date'])->format('Y/m/d');
                        if ($data['to_date']   ?? null) $indicators[] = 'تا ' . \Carbon\Carbon::parse($data['to_date'])->format('Y/m/d');
                        return $indicators ? 'بەروار: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                Filter::make('amount_range')
                    ->label('مەودای بڕی پارە')
                    ->form([
                        TextInput::make('min_amount')->label('کەمترین بڕ')->numeric()->prefix('دینار')->placeholder('١٠٠٠'),
                        TextInput::make('max_amount')->label('زۆرترین بڕ')->numeric()->prefix('دینار')->placeholder('١٠٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['min_amount'], fn ($q) => $q->where('amount', '>=', $data['min_amount']))
                            ->when($data['max_amount'], fn ($q) => $q->where('amount', '<=', $data['max_amount']));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_amount'] ?? null) $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_amount']) . ' د.ع';
                        if ($data['max_amount'] ?? null) $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_amount']) . ' د.ع';
                        return $indicators ? 'بڕی پارە: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                SelectFilter::make('amount_level')
                    ->label('ئاستی بڕی پارە')
                    ->options([
                        'very_large' => 'زۆر گەورە (> ١٠ ملیۆن)',
                        'large'      => 'گەورە (١ ملیۆن - ١٠ ملیۆن)',
                        'medium'     => 'مامناوەند (١٠٠ هەزار - ١ ملیۆن)',
                        'small'      => 'بچوک (١٠ هەزار - ١٠٠ هەزار)',
                        'very_small' => 'زۆر بچوک (< ١٠ هەزار)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return match($data['value']) {
                            'very_large' => $query->where('amount', '>', 10000000),
                            'large'      => $query->whereBetween('amount', [1000000, 10000000]),
                            'medium'     => $query->whereBetween('amount', [100000, 1000000]),
                            'small'      => $query->whereBetween('amount', [10000, 100000]),
                            'very_small' => $query->where('amount', '<', 10000),
                            default      => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'very_large' => 'بڕی زۆر گەورە',
                            'large'      => 'بڕی گەورە',
                            'medium'     => 'بڕی مامناوەند',
                            'small'      => 'بڕی بچوک',
                            'very_small' => 'بڕی زۆر بچوک',
                            default      => null,
                        };
                    })
                    ->columnSpan(1),

                Filter::make('today')
                    ->label('مامەڵەکانی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('transaction_date', today()))
                    ->indicator('ئەمڕۆ'),

                Filter::make('yesterday')
                    ->label('مامەڵەکانی دوێنێ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('transaction_date', today()->subDay()))
                    ->indicator('دوێنێ'),

                Filter::make('this_week')
                    ->label('مامەڵەکانی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                Filter::make('this_month')
                    ->label('مامەڵەکانی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('transaction_date', now()->month)->whereYear('transaction_date', now()->year))
                    ->indicator('ئەم مانگە'),

                Filter::make('this_year')
                    ->label('مامەڵەکانی ئەمساڵ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereYear('transaction_date', now()->year))
                    ->indicator('ئەمساڵ'),

                TernaryFilter::make('has_description')
                    ->label('تێبینی')
                    ->placeholder('هەموو')
                    ->trueLabel('تێبینی هەیە')
                    ->falseLabel('تێبینی نییە')
                    ->queries(
                        true:  fn ($query) => $query->whereNotNull('description'),
                        false: fn ($query) => $query->whereNull('description'),
                    )
                    ->indicator('تێبینی'),

                Filter::make('description_search')
                    ->label('گەڕان لە وەسف')
                    ->form([
                        TextInput::make('search')->label('وشە')->placeholder('وشەی گەڕان ...')->maxLength(100),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['search'], fn ($q) => $q->where('description', 'LIKE', '%' . $data['search'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        return ($data['search'] ?? null) ? 'گەڕان: "' . $data['search'] . '"' : null;
                    }),

                Filter::make('transaction_number_search')
                    ->label('ژمارەی مامەڵە')
                    ->form([
                        TextInput::make('number')->label('ژمارە')->placeholder('TRX-202502-0001')->maxLength(50),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['number'], fn ($q) => $q->where('transaction_number', 'LIKE', '%' . $data['number'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        return ($data['number'] ?? null) ? 'ژ. مامەڵە: ' . $data['number'] : null;
                    }),

                SelectFilter::make('created_by')
                    ->label('تۆمارکراو لەلایەن')
                    ->options(fn () => Transaction::distinct()->pluck('created_by', 'created_by')->filter()->toArray())
                    ->multiple()
                    ->searchable()
                    ->indicator('بەکارهێنەر')
                    ->columnSpan(1),
            ])

            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->filtersFormWidth('lg')
            ->persistFiltersInSession()

            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('فلتەری پێشکەوتوو')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
                    ->size('sm')
            )

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('وردەکاری')
                    ->icon('heroicon-m-eye')
                    ->color('info'),
            ])

            ->bulkActions([])

            ->defaultSort('transaction_date', 'desc')
            ->striped()
            ->poll('10s')

            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('هیچ مامەڵەیەک تۆمار نەکراوە')
            ->emptyStateDescription('کاتێک مامەڵەیەک تۆمار بکەیت، لێرەدا دەردەکەوێت');
    }

    private static function getTypeLabel($type): string
    {
        return match($type) {
            'capital_add'           => '➕ زیادکردنی سەرمایە',
            'capital_withdraw'      => '➖ کەمکردنەوەی سەرمایە',
            'cash_add'              => '💰 زیادکردنی پارە',
            'cash_withdraw'         => '💸 کەمکردنەوەی پارە',
            'sale'                  => '🛒 فرۆشتن',
            'quick_sale'            => '⚡ فرۆشی خێرا',
            'quick_sale_difference' => '⚡ جیاوازی فرۆشی خێرا',
            'purchase'              => '📦 کڕین',
            'salary'                => '👤 مووچە',
            'expense'               => '📉 خەرجی',
            'penalty'               => '⚠️ سزا',
            'credit_payment'        => '💳 دانەوەی قەرز',
            default                 => $type,
        };
    }

    private static function getTypeColor($type): string
    {
        return match($type) {
            'capital_add', 'cash_add', 'sale', 'quick_sale', 'quick_sale_difference', 'credit_payment' => 'success',
            'capital_withdraw', 'cash_withdraw', 'purchase', 'expense', 'penalty'                       => 'danger',
            'salary'                                                                                     => 'warning',
            default                                                                                      => 'gray',
        };
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMoneyHistories::route('/'),
            'view'  => Pages\ViewMoneyHistory::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $today = static::getModel()::whereDate('created_at', today())->count();
        return $today > 0 ? (string) $today : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
