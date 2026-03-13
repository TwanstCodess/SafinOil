<?php
// app/Filament/Resources/TransactionResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'مامەڵە';
    protected static ?string $pluralModelLabel = 'مامەڵە داراییەکان';
    protected static ?string $recordTitleAttribute = 'transaction_number';

    // ✅ هەلمەتی پشکنین — ئایا جۆری مامەڵە فرۆشی خێرایە
    private static function isQuickSale(Transaction $record): bool
    {
        return in_array($record->type, ['quick_sale', 'quick_sale_difference']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری مامەڵە')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_number')
                            ->label('ژمارەی مامەڵە')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('type')
                            ->label('جۆری مامەڵە')
                            ->options([
                                'purchase'               => '📦 کڕین',
                                'sale'                   => '🛒 فرۆشتن',
                                'quick_sale'             => '⚡ فرۆشی خێرا',
                                'quick_sale_difference'  => '⚡ جیاوازی فرۆشی خێرا',
                                'expense'                => '📉 خەرجی',
                                'salary'                 => '👤 مووچە',
                                'penalty'                => '⚠️ سزا',
                                'capital_add'            => '💰 زیادکردنی سەرمایە',
                                'capital_withdraw'       => '💸 کەمکردنەوەی سەرمایە',
                                'cash_add'               => '💵 زیادکردنی پارە',
                                'cash_withdraw'          => '🏧 کەمکردنەوەی پارە',
                                'credit_payment'         => '💳 دانەوەی قەرز',
                            ])
                            ->required()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('balance_before')
                                    ->label('ڕەوشتی قاسە (پێش)')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('balance_after')
                                    ->label('ڕەوشتی قاسە (دوای)')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('ژمارەی سەرچاوە')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('description')
                            ->label('وەسف')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('ڕێکەوتی مامەڵە')
                            ->required()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('created_by')
                            ->label('دروستکراو لەلایەن')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('ژمارەی مامەڵە')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('type')
                    ->label('جۆری مامەڵە')
                    ->badge()
                    ->color(fn (Transaction $record): string => $record->type_color)
                    ->formatStateUsing(fn (Transaction $record): string => $record->type_label)
                    ->searchable()
                    ->sortable(),

                // ✅ پارە: ئەگەر فرۆشی خێرا بوو × 2
                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی پارە')
                    ->formatStateUsing(function ($state, Transaction $record): string {
                        $amount = self::isQuickSale($record)
                            ? floatval($state) * 2
                            : floatval($state);

                        return number_format(abs($amount)) . ' د.ع';
                    })
                    ->sortable()
                    ->color(fn (Transaction $record): string =>
                        $record->is_income ? 'success' : 'danger'
                    )
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                // ✅ وەسف: ئەگەر فرۆشی خێرا بوو لیتر × 2 نیشان دەدرێت
                Tables\Columns\TextColumn::make('description')
                    ->label('وەسف')
                    ->formatStateUsing(function ($state, Transaction $record): string {
                        if (!self::isQuickSale($record) || empty($state)) {
                            return $state ?? '';
                        }

                        // ✅ لیترەکە لە وەسفەکەدا دەگۆڕدرێت × 2
                        // نموونە: "فرۆشتن - 50L بنزین" → "فرۆشتن - 100L بنزین"
                        return preg_replace_callback(
                            '/(\d+(?:\.\d+)?)L/',
                            fn ($matches) => number_format(floatval($matches[1]) * 2, 0) . 'L',
                            $state
                        );
                    })
                    ->limit(40)
                    ->tooltip(function ($state, Transaction $record): string {
                        if (!self::isQuickSale($record) || empty($state)) {
                            return $state ?? '';
                        }
                        return preg_replace_callback(
                            '/(\d+(?:\.\d+)?)L/',
                            fn ($matches) => number_format(floatval($matches[1]) * 2, 0) . 'L',
                            $state
                        );
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('balance_before')
                    ->label('پێش')
                    ->money('IQD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('دوای')
                    ->money('IQD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('ژ. سەرچاوە')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('created_by')
                    ->label('لەلایەن')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('کاتی تۆمارکردن')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('type')
                    ->label('جۆری مامەڵە')
                    ->options([
                        'purchase'               => '📦 کڕین',
                        'sale'                   => '🛒 فرۆشتن',
                        'quick_sale'             => '⚡ فرۆشی خێرا',
                        'quick_sale_difference'  => '⚡ جیاوازی فرۆشی خێرا',
                        'expense'                => '📉 خەرجی',
                        'salary'                 => '👤 مووچە',
                        'penalty'                => '⚠️ سزا',
                        'capital_add'            => '💰 زیادکردنی سەرمایە',
                        'capital_withdraw'       => '💸 کەمکردنەوەی سەرمایە',
                        'cash_add'               => '💵 زیادکردنی پارە',
                        'cash_withdraw'          => '🏧 کەمکردنەوەی پارە',
                        'credit_payment'         => '💳 دانەوەی قەرز',
                    ])
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(12)
                    ->indicator('جۆر')
                    ->placeholder('هەموو جۆرەکان')
                    ->columnSpan(2),

                SelectFilter::make('impact')
                    ->label('کاریگەری لەسەر قاسە')
                    ->options([
                        'income'  => '📈 داهات (زیادکردن)',
                        'expense' => '📉 خەرجی (کەمکردن)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return match($data['value']) {
                            'income'  => $query->whereIn('type', ['sale', 'quick_sale', 'quick_sale_difference', 'cash_add', 'capital_add', 'credit_payment']),
                            'expense' => $query->whereIn('type', ['purchase', 'expense', 'salary', 'penalty', 'cash_withdraw', 'capital_withdraw']),
                            default   => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'income'  => '📈 داهات',
                            'expense' => '📉 خەرجی',
                            default   => null,
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

                Filter::make('balance_before_range')
                    ->label('مەودای ڕەوشتی قاسە (پێش)')
                    ->form([
                        TextInput::make('min_balance_before')->label('کەمترین')->numeric()->prefix('دینار')->placeholder('٠'),
                        TextInput::make('max_balance_before')->label('زۆرترین')->numeric()->prefix('دینار')->placeholder('١٠٠٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['min_balance_before'], fn ($q) => $q->where('balance_before', '>=', $data['min_balance_before']))
                            ->when($data['max_balance_before'], fn ($q) => $q->where('balance_before', '<=', $data['max_balance_before']));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_balance_before'] ?? null) $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_balance_before']) . ' د.ع';
                        if ($data['max_balance_before'] ?? null) $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_balance_before']) . ' د.ع';
                        return $indicators ? 'ڕەوشتی قاسە (پێش): ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                Filter::make('reference_number_search')
                    ->label('ژمارەی سەرچاوە')
                    ->form([
                        TextInput::make('reference')->label('ژمارە')->placeholder('REF-...')->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['reference'], fn ($q) => $q->where('reference_number', 'LIKE', '%' . $data['reference'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        return ($data['reference'] ?? null) ? 'ژ. سەرچاوە: ' . $data['reference'] : null;
                    }),

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

                SelectFilter::make('created_by')
                    ->label('تۆمارکراو لەلایەن')
                    ->options(fn () => Transaction::distinct()->pluck('created_by', 'created_by')->filter()->toArray())
                    ->multiple()
                    ->searchable()
                    ->indicator('بەکارهێنەر')
                    ->columnSpan(1),

                Filter::make('today')
                    ->label('مامەڵەکانی ئەمڕۆ')->toggle()
                    ->query(fn ($query) => $query->whereDate('transaction_date', today()))
                    ->indicator('ئەمڕۆ'),

                Filter::make('yesterday')
                    ->label('مامەڵەکانی دوێنێ')->toggle()
                    ->query(fn ($query) => $query->whereDate('transaction_date', today()->subDay()))
                    ->indicator('دوێنێ'),

                Filter::make('this_week')
                    ->label('مامەڵەکانی ئەم هەفتەیە')->toggle()
                    ->query(fn ($query) => $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                Filter::make('this_month')
                    ->label('مامەڵەکانی ئەم مانگە')->toggle()
                    ->query(fn ($query) => $query->whereMonth('transaction_date', now()->month)->whereYear('transaction_date', now()->year))
                    ->indicator('ئەم مانگە'),

                Filter::make('this_year')
                    ->label('مامەڵەکانی ئەمساڵ')->toggle()
                    ->query(fn ($query) => $query->whereYear('transaction_date', now()->year))
                    ->indicator('ئەمساڵ'),

                TernaryFilter::make('has_description')
                    ->label('وەسف')
                    ->placeholder('هەموو')
                    ->trueLabel('وەسفی هەیە')
                    ->falseLabel('وەسفی نییە')
                    ->queries(
                        true:  fn ($query) => $query->whereNotNull('description'),
                        false: fn ($query) => $query->whereNull('description'),
                    )
                    ->indicator('وەسف'),
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
                    ->label('بینین')
                    ->icon('heroicon-m-eye')
                    ->color('info'),
            ])

            ->bulkActions([])

            ->emptyStateIcon('heroicon-o-arrow-path')
            ->emptyStateHeading('هیچ مامەڵەیەک تۆمار نەکراوە')
            ->emptyStateDescription('کاتێک مامەڵەیەک تۆمار بکەیت، لێرەدا دەردەکەوێت')

            ->defaultSort('transaction_date', 'desc')
            ->striped()
            ->poll('10s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view'  => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['transaction_number', 'reference_number', 'description'];
    }

    public static function getNavigationBadge(): ?string
    {
        $today = static::getModel()::whereDate('transaction_date', today())->count();
        return $today > 0 ? (string) $today : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
