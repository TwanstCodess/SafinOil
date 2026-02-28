<?php
// app/Filament/Resources/PenaltyResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\PenaltyResource\Pages;
use App\Models\Penalty;
use App\Models\Employee;
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

class PenaltyResource extends Resource
{
    protected static ?string $model = Penalty::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی کارمەندان';
    protected static ?string $modelLabel = 'سزا';
    protected static ?string $pluralModelLabel = 'سزاکان';
    protected static ?string $recordTitleAttribute = 'reason';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری سزا')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('کارمەند')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی سزا')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('penalty_date')
                            ->label('ڕێکەوتی سزا')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('reason')
                            ->label('هۆکار')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->label('تێبینی')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('کارمەند')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی سزا')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('reason')
                    ->label('هۆکار')
                    ->searchable()
                    ->badge()
                    ->color('warning')
                    ->limit(30)
                    ->tooltip(fn ($state): string => $state ?? ''),

                Tables\Columns\TextColumn::make('penalty_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('تێبینی')
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
                // فلتەری کارمەند
                SelectFilter::make('employee_id')
                    ->label('کارمەند')
                    ->relationship('employee', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->indicator('کارمەند')
                    ->placeholder('هەموو کارمەندان')
                    ->columnSpan(2),

                // فلتەری مەودای بەروار
                Filter::make('penalty_date')
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
                                fn ($q) => $q->whereDate('penalty_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('penalty_date', '<=', $data['until'])
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

                // فلتەری مەودای بڕی سزا
                Filter::make('amount_range')
                    ->label('مەودای بڕی سزا')
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
                        return $indicators ? 'بڕی سزا: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری ئاستی سزا
                SelectFilter::make('amount_level')
                    ->label('ئاستی سزا')
                    ->options([
                        'very_high' => 'زۆر بەرز (> ١٠٠٠٠٠)',
                        'high' => 'بەرز (٥٠٠٠٠ - ١٠٠٠٠٠)',
                        'medium' => 'مامناوەند (٢٥٠٠٠ - ٥٠٠٠٠)',
                        'low' => 'کەم (١٠٠٠٠ - ٢٥٠٠٠)',
                        'very_low' => 'زۆر کەم (< ١٠٠٠٠)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'very_high' => $query->where('amount', '>', 100000),
                            'high' => $query->whereBetween('amount', [50000, 100000]),
                            'medium' => $query->whereBetween('amount', [25000, 50000]),
                            'low' => $query->whereBetween('amount', [10000, 25000]),
                            'very_low' => $query->where('amount', '<', 10000),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'very_high' => 'سزای زۆر بەرز',
                            'high' => 'سزای بەرز',
                            'medium' => 'سزای مامناوەند',
                            'low' => 'سزای کەم',
                            'very_low' => 'سزای زۆر کەم',
                            default => null,
                        };
                    })
                    ->columnSpan(1),

                // فلتەری هۆکار
                SelectFilter::make('reason')
                    ->label('هۆکار')
                    ->options(function () {
                        return Penalty::distinct()
                            ->pluck('reason', 'reason')
                            ->filter()
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable()
                    ->indicator('هۆکار')
                    ->columnSpan(2),

                // فلتەری سزاکانی ئەمڕۆ
                Filter::make('today')
                    ->label('سزاکانی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('penalty_date', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری سزاکانی دوێنێ
                Filter::make('yesterday')
                    ->label('سزاکانی دوێنێ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('penalty_date', today()->subDay()))
                    ->indicator('دوێنێ'),

                // فلتەری سزاکانی ئەم هەفتەیە
                Filter::make('this_week')
                    ->label('سزاکانی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('penalty_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری سزاکانی ئەم مانگە
                Filter::make('this_month')
                    ->label('سزاکانی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('penalty_date', now()->month)
                        ->whereYear('penalty_date', now()->year))
                    ->indicator('ئەم مانگە'),

                // فلتەری سزاکانی ئەمساڵ
                Filter::make('this_year')
                    ->label('سزاکانی ئەمساڵ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereYear('penalty_date', now()->year))
                    ->indicator('ئەمساڵ'),

                // فلتەری تێبینی
                TernaryFilter::make('has_notes')
                    ->label('تێبینی')
                    ->placeholder('هەموو')
                    ->trueLabel('تێبینی هەیە')
                    ->falseLabel('تێبینی نییە')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('notes'),
                        false: fn ($query) => $query->whereNull('notes'),
                    )
                    ->indicator('تێبینی'),

                // فلتەری گەڕان لە تێبینی
                Filter::make('notes_search')
                    ->label('گەڕان لە تێبینی')
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
                                fn ($q) => $q->where('notes', 'LIKE', '%' . $data['search'] . '%')
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
                        ->modalHeading('سڕینەوەی سزا')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم سزایە؟')
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
                        ->modalHeading('سڕینەوەی سزاکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم سزایانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-exclamation-triangle')
            ->emptyStateHeading('هیچ سزایەک نییە')
            ->emptyStateDescription('یەکەم سزا تۆمار بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('تۆمارکردنی سزا')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('penalty_date', 'desc')
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
            'index' => Pages\ListPenalties::route('/'),
            'create' => Pages\CreatePenalty::route('/create'),
            'edit' => Pages\EditPenalty::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $thisMonth = static::getModel()::whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->count();
        return $thisMonth > 0 ? (string) $thisMonth : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
