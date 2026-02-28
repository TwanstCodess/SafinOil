<?php
// app/Filament/Resources/SalaryResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Models\Salary;
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

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی کارمەندان';
    protected static ?string $modelLabel = 'مووچە';
    protected static ?string $pluralModelLabel = 'مووچەکان';
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری مووچە')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('کارمەند')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $employee = Employee::find($state);
                                if ($employee) {
                                    $set('amount', $employee->salary);
                                    $deductions = $get('deductions') ?? 0;
                                    $set('net_amount', $employee->salary - $deductions);
                                }
                            }),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی مووچە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $deductions = $get('deductions') ?? 0;
                                $set('net_amount', $state - $deductions);
                            }),
                        Forms\Components\TextInput::make('deductions')
                            ->label('بڕی سزا')
                            ->numeric()
                            ->default(0)
                            ->prefix('دینار')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $amount = $get('amount') ?? 0;
                                $set('net_amount', $amount - $state);
                            }),
                        Forms\Components\TextInput::make('net_amount')
                            ->label('مووچەی پاک')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled()
                            ->dehydrated(true)
                            ->default(0),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('ڕێکەوتی پێدان')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('month')
                            ->label('مانگ')
                            ->options([
                                '1' => 'ڕێبەندان',
                                '2' => 'ڕەشەمە',
                                '3' => 'نەورۆز',
                                '4' => 'گوڵان',
                                '5' => 'جۆزەردان',
                                '6' => 'پووشپەڕ',
                                '7' => 'گەلاوێژ',
                                '8' => 'خەرمانان',
                                '9' => 'ڕەزبەر',
                                '10' => 'گەڵاڕێزان',
                                '11' => 'سەرماوەز',
                                '12' => 'بەفرانبار',
                            ])
                            ->required()
                            ->default(now()->month)
                            ->searchable(),
                        Forms\Components\TextInput::make('year')
                            ->label('ساڵ')
                            ->numeric()
                            ->required()
                            ->default(now()->year)
                            ->minValue(2020)
                            ->maxValue(now()->year + 1),
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
                    ->icon('heroicon-m-user')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('month')
                    ->label('مانگ')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => self::getMonthName($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('ساڵ')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('مووچە')
                    ->money('IQD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deductions')
                    ->label('سزا')
                    ->money('IQD')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('مووچەی پاک')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('ڕێکەوتی پێدان')
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

                // فلتەری مانگ
                SelectFilter::make('month')
                    ->label('مانگ')
                    ->options([
                        '1' => 'ڕێبەندان',
                        '2' => 'ڕەشەمە',
                        '3' => 'نەورۆز',
                        '4' => 'گوڵان',
                        '5' => 'جۆزەردان',
                        '6' => 'پووشپەڕ',
                        '7' => 'گەلاوێژ',
                        '8' => 'خەرمانان',
                        '9' => 'ڕەزبەر',
                        '10' => 'گەڵاڕێزان',
                        '11' => 'سەرماوەز',
                        '12' => 'بەفرانبار',
                    ])
                    ->multiple()
                    ->searchable()
                    ->indicator('مانگ')
                    ->columnSpan(1),

                // فلتەری ساڵ
                SelectFilter::make('year')
                    ->label('ساڵ')
                    ->options(function () {
                        $years = Salary::distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();

                        // ساڵی ئێستا زیاد بکە ئەگەر نییە
                        $currentYear = now()->year;
                        if (!in_array($currentYear, $years)) {
                            $years[$currentYear] = $currentYear;
                        }

                        return $years;
                    })
                    ->multiple()
                    ->searchable()
                    ->indicator('ساڵ')
                    ->columnSpan(1),

                // فلتەری مەودای مووچە
                Filter::make('amount_range')
                    ->label('مەودای مووچە')
                    ->form([
                        TextInput::make('min_amount')
                            ->label('کەمترین مووچە')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('٢٥٠٠٠٠'),
                        TextInput::make('max_amount')
                            ->label('زۆرترین مووچە')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠٠'),
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
                        return $indicators ? 'مووچە: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای مووچەی پاک
                Filter::make('net_amount_range')
                    ->label('مەودای مووچەی پاک')
                    ->form([
                        TextInput::make('min_net')
                            ->label('کەمترین مووچەی پاک')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('٢٥٠٠٠٠'),
                        TextInput::make('max_net')
                            ->label('زۆرترین مووچەی پاک')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_net'],
                                fn ($q) => $q->where('net_amount', '>=', $data['min_net'])
                            )
                            ->when(
                                $data['max_net'],
                                fn ($q) => $q->where('net_amount', '<=', $data['max_net'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_net'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_net']) . ' د.ع';
                        }
                        if ($data['max_net'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_net']) . ' د.ع';
                        }
                        return $indicators ? 'مووچەی پاک: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای سزا
                Filter::make('deductions_range')
                    ->label('مەودای سزا')
                    ->form([
                        TextInput::make('min_deductions')
                            ->label('کەمترین سزا')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('٠'),
                        TextInput::make('max_deductions')
                            ->label('زۆرترین سزا')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_deductions'],
                                fn ($q) => $q->where('deductions', '>=', $data['min_deductions'])
                            )
                            ->when(
                                $data['max_deductions'],
                                fn ($q) => $q->where('deductions', '<=', $data['max_deductions'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_deductions'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_deductions']) . ' د.ع';
                        }
                        if ($data['max_deductions'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_deductions']) . ' د.ع';
                        }
                        return $indicators ? 'سزا: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری سزادار
                TernaryFilter::make('has_deductions')
                    ->label('سزادار')
                    ->placeholder('هەموو')
                    ->trueLabel('سزادار')
                    ->falseLabel('بێ سزا')
                    ->queries(
                        true: fn ($query) => $query->where('deductions', '>', 0),
                        false: fn ($query) => $query->where('deductions', '=', 0),
                    )
                    ->indicator('سزادار')
                    ->columnSpan(1),

                // فلتەری مەودای بەرواری پێدان
                Filter::make('payment_date')
                    ->label('مەودای بەرواری پێدان')
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
                                fn ($q) => $q->whereDate('payment_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('payment_date', '<=', $data['until'])
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
                        return $indicators ? 'بەرواری پێدان: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مووچەی ئەمڕۆ
                Filter::make('today')
                    ->label('مووچەی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('payment_date', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری مووچەی ئەم هەفتەیە
                Filter::make('this_week')
                    ->label('مووچەی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری مووچەی ئەم مانگە
                Filter::make('this_month')
                    ->label('مووچەی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('payment_date', now()->month)
                        ->whereYear('payment_date', now()->year))
                    ->indicator('ئەم مانگە'),

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

                    Tables\Actions\Action::make('view_employee')
                        ->label('بینینی کارمەند')
                        ->icon('heroicon-m-user')
                        ->color(Color::Blue)
                        ->url(fn ($record): string => route('filament.admin.resources.employees.edit', $record->employee_id))
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->label('سڕینەوە')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->modalHeading('سڕینەوەی مووچە')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم مووچەیە؟')
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
                        ->modalHeading('سڕینەوەی مووچەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم مووچانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('هیچ مووچەیەک تۆمار نەکراوە')
            ->emptyStateDescription('یەکەم مووچە تۆمار بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('تۆمارکردنی مووچە')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('payment_date', 'desc')
            ->striped()
            ->poll('30s');
    }

    private static function getMonthName($month): string
    {
        return match((string) $month) {
            '1' => 'ڕێبەندان',
            '2' => 'ڕەشەمە',
            '3' => 'نەورۆز',
            '4' => 'گوڵان',
            '5' => 'جۆزەردان',
            '6' => 'پووشپەڕ',
            '7' => 'گەلاوێژ',
            '8' => 'خەرمانان',
            '9' => 'ڕەزبەر',
            '10' => 'گەڵاڕێزان',
            '11' => 'سەرماوەز',
            '12' => 'بەفرانبار',
            default => $month,
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
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'edit' => Pages\EditSalary::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $thisMonth = static::getModel()::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->count();
        return $thisMonth > 0 ? (string) $thisMonth : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
