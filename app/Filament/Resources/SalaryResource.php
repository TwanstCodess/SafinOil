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
                                    // **حسابکردنی مووچەی پاک = مووچە - سزا**
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
                                // **کاتێک مووچە دەگۆڕێت، مووچەی پاکیش دەگۆڕێت**
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
                                // **کاتێک سزا زیاد دەکەیت، مووچەی پاک کەم دەبێتەوە**
                                $set('net_amount', $amount - $state);
                            })
                            ->helperText('ئەم بڕە لە مووچەی کارمەند کەم دەکرێتەوە'),
                        Forms\Components\TextInput::make('net_amount')
                            ->label('مووچەی پاک')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled()
                            ->dehydrated(true)
                            ->default(0)
                            ->helperText('ئەمە ئەو بڕەیە کە لە قاسە کەم دەکرێتەوە'),
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
                    ->icon('heroicon-m-user'),

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
                    ->description(fn ($record) => $record->deductions > 0 ? 'سزا: ' . number_format($record->deductions) . ' د.ع' : null),

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
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('کارمەند')
                    ->relationship('employee', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

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
                    ->searchable(),

                SelectFilter::make('year')
                    ->label('ساڵ')
                    ->options(function () {
                        $years = Salary::distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();
                        $currentYear = now()->year;
                        if (!in_array($currentYear, $years)) {
                            $years[$currentYear] = $currentYear;
                        }
                        return $years;
                    })
                    ->multiple(),

                TernaryFilter::make('has_deductions')
                    ->label('سزادار')
                    ->placeholder('هەموو')
                    ->trueLabel('سزادار')
                    ->falseLabel('بێ سزا')
                    ->queries(
                        true: fn ($query) => $query->where('deductions', '>', 0),
                        false: fn ($query) => $query->where('deductions', '=', 0),
                    ),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('فلتەری پێشکەوتوو')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
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
                        ->modalHeading('سڕینەوەی مووچە')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم مووچەیە؟ پارەکە دەگەڕێتەوە قاسە')
                        ->modalSubmitActionLabel('بەڵێ، بسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ])
                ->label('کردارەکان')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان')
                        ->modalHeading('سڕینەوەی مووچەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم مووچانە؟ پارەکان دەگەڕێنەوە قاسە')
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
            ->striped();
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
