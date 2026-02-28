<?php
// app/Filament/Resources/ExpenseResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
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
use Filament\Support\Colors\Color;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'خەرجی';
    protected static ?string $pluralModelLabel = 'خەرجییەکان';
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری خەرجی')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('ناونیشان')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('ڕێکەوت')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('category')
                            ->label('جۆری خەرجی')
                            ->options(function () {
                                return Expense::distinct()
                                    ->pluck('category', 'category')
                                    ->filter()
                                    ->toArray();
                            })
                            ->searchable()
                            ->placeholder('جۆرێک هەڵبژێرە'),
                        Forms\Components\Textarea::make('description')
                            ->label('وەسف')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('ناونیشان')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی پارە')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('category')
                    ->label('جۆر')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'سووتەمەنی' => 'warning',
                        'کارەبا' => 'info',
                        'ئاو' => 'info',
                        'خواردن' => 'success',
                        'کرێ' => 'primary',
                        'گواستنەوە' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expense_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('description')
                    ->label('وەسف')
                    ->limit(30)
                    ->tooltip(fn ($state): string => $state ?? '')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('بەرواری تۆمارکردن')
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // **فلتەرە پڕۆفیشناڵەکان**
            ->filters([
                // فلتەری جۆری خەرجی
                SelectFilter::make('category')
                    ->label('جۆری خەرجی')
                    ->options(function () {
                        return Expense::distinct()
                            ->pluck('category', 'category')
                            ->filter()
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->indicator('جۆر')
                    ->placeholder('هەموو جۆرەکان')
                    ->columnSpan(2),

                // فلتەری مەودای بەروار
                Filter::make('expense_date')
                    ->label('مەودای بەروار')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('until')
                            ->label('تا ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('expense_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('expense_date', '<=', $data['until'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'لە ' . \Carbon\Carbon::parse($data['from'])->format('Y/m/d');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'تا ' . \Carbon\Carbon::parse($data['until'])->format('Y/m/d');
                        }
                        return $indicators ? 'بەروار: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای بڕی پارە
                Filter::make('amount_range')
                    ->label('مەودای بڕی پارە')
                    ->form([
                        TextInput::make('min_amount')
                            ->label('کەمترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠'),
                        TextInput::make('max_amount')
                            ->label('زۆرترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn ($q) => $q->where('amount', '>=', $data['min_amount'])
                            )
                            ->when(
                                $data['max_amount'],
                                fn ($q) => $q->where('amount', '<=', $data['max_amount'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_amount'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_amount']) . ' د.ع';
                        }
                        if ($data['max_amount'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_amount']) . ' د.ع';
                        }
                        return $indicators ? 'بڕی پارە: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری ئاستی خەرجی
                SelectFilter::make('amount_level')
                    ->label('ئاستی خەرجی')
                    ->options([
                        'very_high' => 'زۆر بەرز (> ١٠٠٠٠٠٠)',
                        'high' => 'بەرز (٥٠٠٠٠٠ - ١٠٠٠٠٠٠)',
                        'medium' => 'مامناوەند (١٠٠٠٠٠ - ٥٠٠٠٠٠)',
                        'low' => 'کەم (١٠٠٠٠ - ١٠٠٠٠٠)',
                        'very_low' => 'زۆر کەم (< ١٠٠٠٠)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'very_high' => $query->where('amount', '>', 1000000),
                            'high' => $query->whereBetween('amount', [500000, 1000000]),
                            'medium' => $query->whereBetween('amount', [100000, 500000]),
                            'low' => $query->whereBetween('amount', [10000, 100000]),
                            'very_low' => $query->where('amount', '<', 10000),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'very_high' => 'خەرجی زۆر بەرز',
                            'high' => 'خەرجی بەرز',
                            'medium' => 'خەرجی مامناوەند',
                            'low' => 'خەرجی کەم',
                            'very_low' => 'خەرجی زۆر کەم',
                            default => null,
                        };
                    })
                    ->columnSpan(1),

                // فلتەری خەرجی ئەمڕۆ
                Filter::make('today')
                    ->label('خەرجی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('expense_date', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری خەرجی دوێنێ
                Filter::make('yesterday')
                    ->label('خەرجی دوێنێ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('expense_date', today()->subDay()))
                    ->indicator('دوێنێ'),

                // فلتەری خەرجی ئەم هەفتەیە
                Filter::make('this_week')
                    ->label('خەرجی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('expense_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری خەرجی ئەم مانگە
                Filter::make('this_month')
                    ->label('خەرجی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('expense_date', now()->month)
                        ->whereYear('expense_date', now()->year))
                    ->indicator('ئەم مانگە'),

                // فلتەری خەرجی ئەمساڵ
                Filter::make('this_year')
                    ->label('خەرجی ئەمساڵ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereYear('expense_date', now()->year))
                    ->indicator('ئەمساڵ'),

                // فلتەری خەرجی کە تێبینیان هەیە
                TernaryFilter::make('has_description')
                    ->label('تێبینی')
                    ->placeholder('هەموو')
                    ->trueLabel('تێبینی هەیە')
                    ->falseLabel('تێبینی نییە')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('description'),
                        false: fn ($query) => $query->whereNull('description'),
                    )
                    ->indicator('تێبینی'),

                // فلتەری بەپێی وەسف
                Filter::make('description_search')
                    ->label('گەڕان لە وەسف')
                    ->form([
                        TextInput::make('search')
                            ->label('وشە')
                            ->placeholder('وشەی گەڕان ...')
                            ->maxLength(100),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['search'],
                                fn ($q) => $q->where('description', 'LIKE', '%' . $data['search'] . '%')
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['search'] ?? null) {
                            return 'گەڕان: "' . $data['search'] . '"';
                        }
                        return null;
                    }),
            ])

            // ڕێکخستنی فلتەرەکان
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->filtersFormWidth('lg')
            ->persistFiltersInSession()

            // دوگمەی فلتەر
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('فلتەری پێشکەوتوو')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
                    ->size('sm')
            )

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('بینین')
                        ->icon('heroicon-m-eye')
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->label('دەستکاری')
                        ->icon('heroicon-m-pencil')
                        ->color('warning'),

                    Tables\Actions\DeleteAction::make()
                        ->label('سڕینەوە')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->modalHeading('سڕینەوەی خەرجی')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم خەرجییە؟')
                        ->modalSubmitActionLabel('بەڵێ، بسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ])
                ->label('کردارەکان')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->size('sm'),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان')
                        ->modalHeading('سڕینەوەی خەرجییە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم خەرجیانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-arrow-trending-down')
            ->emptyStateHeading('هیچ خەرجییەک نییە')
            ->emptyStateDescription('یەکەم خەرجی تۆمار بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('تۆمارکردنی خەرجی')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('expense_date', 'desc')
            ->striped()
            ->poll('30s');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayTotal = static::getModel()::whereDate('expense_date', today())->sum('amount');
        return $todayTotal > 0 ? number_format($todayTotal / 1000, 1) . 'K' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
