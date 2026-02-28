<?php
// app/Filament/Resources/TypeResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\TypeResource\Pages;
use App\Models\Type;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Colors\Color;

class TypeResource extends Resource
{
    protected static ?string $model = Type::class;
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'بەرهەمەکان';
    protected static ?string $modelLabel = 'جۆر';
    protected static ?string $pluralModelLabel = 'جۆرەکان';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری جۆر')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناو')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('key')
                            ->label('کلیل (بە ئینگلیزی)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('بۆ ناسینەوەی جۆر لە کۆددا بەکار دێت (fuel, oil, gas)'),
                        Forms\Components\Select::make('color')
                            ->label('ڕەنگ')
                            ->options([
                                'warning' => '🟡 زەرد',
                                'success' => '🟢 سەوز',
                                'info' => '🔵 شین',
                                'danger' => '🔴 سور',
                                'gray' => '⚪ ڕەساسی',
                            ])
                            ->default('gray')
                            ->required(),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('ناو')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('key')
                    ->label('کلیل')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('ڕەنگ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('categories_count')
                    ->label('ژمارەی کاتیگۆری')
                    ->counts('categories')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('بەرواری دروستبوون')
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // **فلتەرە پڕۆفیشناڵەکان**
            ->filters([
                // فلتەری ناو
                Filter::make('name')
                    ->label('ناو')
                    ->form([
                        TextInput::make('name_search')
                            ->label('ناو')
                            ->placeholder('ناوی جۆر ...')
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['name_search'], fn ($q) => $q->where('name', 'LIKE', '%' . $data['name_search'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['name_search'] ?? null) {
                            return 'ناو: ' . $data['name_search'];
                        }
                        return null;
                    }),

                // فلتەری کلیل
                Filter::make('key')
                    ->label('کلیل')
                    ->form([
                        TextInput::make('key_search')
                            ->label('کلیل')
                            ->placeholder('fuel, oil, gas ...')
                            ->maxLength(50),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['key_search'], fn ($q) => $q->where('key', 'LIKE', '%' . $data['key_search'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['key_search'] ?? null) {
                            return 'کلیل: ' . $data['key_search'];
                        }
                        return null;
                    }),

                // فلتەری ڕەنگ
                SelectFilter::make('color')
                    ->label('ڕەنگ')
                    ->options([
                        'warning' => '🟡 زەرد',
                        'success' => '🟢 سەوز',
                        'info' => '🔵 شین',
                        'danger' => '🔴 سور',
                        'gray' => '⚪ ڕەساسی',
                    ])
                    ->multiple()
                    ->searchable()
                    ->indicator('ڕەنگ')
                    ->placeholder('هەموو ڕەنگەکان')
                    ->columnSpan(2),

                // فلتەری ژمارەی کاتیگۆری
                Filter::make('categories_count_range')
                    ->label('ژمارەی کاتیگۆری')
                    ->form([
                        TextInput::make('min_count')
                            ->label('کەمترین')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('٠'),
                        TextInput::make('max_count')
                            ->label('زۆرترین')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('١٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['min_count'] !== null, fn ($q) => $q->has('categories', '>=', $data['min_count']))
                            ->when($data['max_count'] !== null, fn ($q) => $q->has('categories', '<=', $data['max_count']));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if (isset($data['min_count']) && $data['min_count'] !== '') {
                            $indicators[] = 'کەمتر نییە لە ' . $data['min_count'] . ' کاتیگۆری';
                        }
                        if (isset($data['max_count']) && $data['max_count'] !== '') {
                            $indicators[] = 'زیاتر نییە لە ' . $data['max_count'] . ' کاتیگۆری';
                        }
                        return $indicators ? 'ژمارەی کاتیگۆری: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری جۆرە بەکارهاتووەکان
                TernaryFilter::make('has_categories')
                    ->label('کاتیگۆری')
                    ->placeholder('هەموو')
                    ->trueLabel('کاتیگۆری هەیە')
                    ->falseLabel('کاتیگۆری نییە')
                    ->queries(
                        true: fn ($query) => $query->has('categories', '>', 0),
                        false: fn ($query) => $query->doesntHave('categories'),
                    )
                    ->indicator('کاتیگۆری'),

                // فلتەری گەڕان لە وەسف
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
                            ->when($data['search'], fn ($q) => $q->where('description', 'LIKE', '%' . $data['search'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['search'] ?? null) {
                            return 'گەڕان: "' . $data['search'] . '"';
                        }
                        return null;
                    }),

                // فلتەری بەرواری دروستبوون
                Filter::make('created_at')
                    ->label('بەرواری دروستبوون')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('لە')
                            ->placeholder('YYYY-MM-DD'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('تا')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
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

                // فلتەری ئەمڕۆ
                \Filament\Tables\Filters\Filter::make('today')
                    ->label('جۆرەکانی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری ئەم هەفتەیە
                \Filament\Tables\Filters\Filter::make('this_week')
                    ->label('جۆرەکانی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری ئەم مانگە
                \Filament\Tables\Filters\Filter::make('this_month')
                    ->label('جۆرەکانی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year))
                    ->indicator('ئەم مانگە'),
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
                        ->modalHeading('سڕینەوەی جۆر')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم جۆرە؟')
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
                        ->modalHeading('سڕینەوەی جۆرە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم جۆرانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-table-cells')
            ->emptyStateHeading('هیچ جۆرێک نییە')
            ->emptyStateDescription('یەکەم جۆر دروست بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی جۆر')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('name')
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
            'index' => Pages\ListTypes::route('/'),
            'create' => Pages\CreateType::route('/create'),
            'edit' => Pages\EditType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
