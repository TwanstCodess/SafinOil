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
use Filament\Forms\Components\DatePicker;
use Filament\Support\Colors\Color;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'مامەڵە';
    protected static ?string $pluralModelLabel = 'مامەڵە داراییەکان';
    protected static ?string $recordTitleAttribute = 'transaction_number';

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
                                'purchase' => 'کڕین',
                                'sale' => 'فرۆشتن',
                                'expense' => 'خەرجی',
                                'salary' => 'مووچە',
                                'penalty' => 'سزا',
                                'cash_add' => 'زیادکردنی پارە بۆ قاسە',
                                'cash_withdraw' => 'کەمکردنەوەی پارە لە قاسە',
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

                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی پارە')
                    ->money('IQD')
                    ->sortable()
                    ->color(fn (Transaction $record): string =>
                        $record->is_income ? 'success' : 'danger'
                    )
                    ->weight('bold'),

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

                Tables\Columns\TextColumn::make('description')
                    ->label('وەسف')
                    ->limit(30)
                    ->tooltip(fn ($state): string => $state ?? '')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable(),

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
                        'purchase' => 'کڕین',
                        'sale' => 'فرۆشتن',
                        'expense' => 'خەرجی',
                        'salary' => 'مووچە',
                        'penalty' => 'سزا',
                        'cash_add' => 'زیادکردنی پارە',
                        'cash_withdraw' => 'کەمکردنەوەی پارە',
                    ])
                    ->multiple(),

                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from')->label('لە ڕێکەوتی'),
                        DatePicker::make('until')->label('تا ڕێکەوتی'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('transaction_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('transaction_date', '<=', $data['until']));
                    }),

                Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('کەمترین بڕ')
                            ->numeric()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('زۆرترین بڕ')
                            ->numeric()
                            ->prefix('دینار'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_amount'], fn ($q) => $q->where('amount', '>=', $data['min_amount']))
                            ->when($data['max_amount'], fn ($q) => $q->where('amount', '<=', $data['max_amount']));
                    }),

                Tables\Filters\TernaryFilter::make('is_income')
                    ->label('داهات/خەرجی')
                    ->placeholder('هەموو مامەڵەکان')
                    ->trueLabel('داهات (فرۆشتن و زیادکردن)')
                    ->falseLabel('خەرجی (کڕین و مووچە و ...)')
                    ->queries(
                        true: fn ($query) => $query->whereIn('type', ['sale', 'cash_add']),
                        false: fn ($query) => $query->whereIn('type', ['purchase', 'expense', 'salary', 'penalty', 'cash_withdraw']),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('بینین'),
            ])
            ->bulkActions([
                // هیچ bulk actionـێک نییە چونکە مامەڵەکان نابێت بسڕدرێنەوە
            ])
            ->defaultSort('transaction_date', 'desc')
            ->poll('10s');
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['transaction_number', 'reference_number', 'description'];
    }
}
