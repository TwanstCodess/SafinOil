<?php
// app/Filament/Resources/CategoryResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Models\Type;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'بەرهەمەکان';
    protected static ?string $modelLabel = 'کاتیگۆری';
    protected static ?string $pluralModelLabel = 'کاتیگۆریەکان';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری کاتیگۆری')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناوی کاتیگۆری')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('نمونە: بەنزین 95، بەنزین 92، ...'),

                        Forms\Components\Select::make('type_id')
                            ->label('جۆری بەرهەم')
                            ->options(Type::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->placeholder('جۆری بەرهەم هەڵبژێرە')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('ناوی جۆر')
                                    ->required(),
                                Forms\Components\TextInput::make('key')
                                    ->label('کلیل (بە ئینگلیزی)')
                                    ->required()
                                    ->unique('types', 'key')
                                    ->helperText('نمونە: fuel, oil, gas'),
                                Forms\Components\Select::make('color')
                                    ->label('ڕەنگ')
                                    ->options([
                                        'warning' => 'زەرد',
                                        'success' => 'سەوز',
                                        'info' => 'شین',
                                        'danger' => 'سور',
                                        'gray' => 'ڕەساسی',
                                    ])
                                    ->default('gray'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return Type::create($data);
                            }),

                        Forms\Components\TextInput::make('current_price')
                            ->label('نرخی فرۆشتن (لیترێک)')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(0)
                            ->step(50),

                        Forms\Components\TextInput::make('purchase_price')
                            ->label('نرخی کڕین (لیترێک)')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(0)
                            ->step(50),

                        Forms\Components\TextInput::make('stock_liters')
                            ->label('بڕی کۆگا')
                            ->numeric()
                            ->required()
                            ->suffix('لیتر')
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('ناوی کاتیگۆری')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('type.name')
                    ->label('جۆر')
                    ->badge()
                    ->color(fn ($record) => $record->type?->getFilamentColor() ?? 'gray')
                    ->searchable()
                    ->sortable()
                    ->icon(fn ($record) => match($record->type?->key) {
                        'fuel' => 'heroicon-o-fire',
                        'oil' => 'heroicon-o-beaker',
                        'gas' => 'heroicon-o-cloud',
                        default => 'heroicon-o-tag',
                    }),

                Tables\Columns\TextColumn::make('current_price')
                    ->label('نرخی فرۆشتن')
                    ->money('IQD')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('نرخی کڕین')
                    ->money('IQD')
                    ->sortable()
                    ->toggleable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('stock_liters')
                    ->label('کۆگا')
                    ->suffix(' لیتر')
                    ->badge()
                    ->color(fn ($state): string =>
                        $state > 1000 ? 'success' : ($state > 100 ? 'warning' : 'danger')
                    )
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('ڕێژەی قازانج')
                    ->getStateUsing(function ($record) {
                        if ($record->purchase_price == 0) return 0;
                        return (($record->current_price - $record->purchase_price) / $record->purchase_price) * 100;
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 10 ? 'warning' : 'danger'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('ڕێکەوتی دروستبوون')
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // **فلتەرە پڕۆفیشناڵەکان**
            ->filters([
                // فلتەری جۆر - بە شێوازی گرافیک
                SelectFilter::make('type_id')
                    ->label('جۆری بەرهەم')
                    ->relationship('type', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10)
                    ->indicator('جۆر')
                    ->placeholder('هەموو جۆرەکان')
                    ->columnSpan(2),

                // فلتەری مەودای نرخی فرۆشتن
                Filter::make('current_price_range')
                    ->label('مەودای نرخی فرۆشتن')
                    ->form([
                        TextInput::make('min_current_price')
                            ->label('کەمترین نرخ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠'),
                        TextInput::make('max_current_price')
                            ->label('زۆرترین نرخ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_current_price'],
                                fn ($q) => $q->where('current_price', '>=', $data['min_current_price'])
                            )
                            ->when(
                                $data['max_current_price'],
                                fn ($q) => $q->where('current_price', '<=', $data['max_current_price'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_current_price'] ?? null) {
                            $indicators[] = 'لە ' . number_format($data['min_current_price']) . ' د.ع';
                        }
                        if ($data['max_current_price'] ?? null) {
                            $indicators[] = 'تا ' . number_format($data['max_current_price']) . ' د.ع';
                        }
                        return $indicators ? 'نرخی فرۆشتن: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای نرخی کڕین
                Filter::make('purchase_price_range')
                    ->label('مەودای نرخی کڕین')
                    ->form([
                        TextInput::make('min_purchase_price')
                            ->label('کەمترین نرخ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠'),
                        TextInput::make('max_purchase_price')
                            ->label('زۆرترین نرخ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_purchase_price'],
                                fn ($q) => $q->where('purchase_price', '>=', $data['min_purchase_price'])
                            )
                            ->when(
                                $data['max_purchase_price'],
                                fn ($q) => $q->where('purchase_price', '<=', $data['max_purchase_price'])
                            );
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری ئاستی کۆگا
                SelectFilter::make('stock_level')
                    ->label('ئاستی کۆگا')
                    ->options([
                        'very_low' => 'زۆر کەم (کەمتر لە ١٠٠ لیتر)',
                        'low' => 'کەم (١٠٠ - ٥٠٠ لیتر)',
                        'medium' => 'مامناوەند (٥٠٠ - ١٠٠٠ لیتر)',
                        'high' => 'زۆر (زیاتر لە ١٠٠٠ لیتر)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'very_low' => $query->where('stock_liters', '<', 100),
                            'low' => $query->whereBetween('stock_liters', [100, 500]),
                            'medium' => $query->whereBetween('stock_liters', [500, 1000]),
                            'high' => $query->where('stock_liters', '>', 1000),
                            default => $query,
                        };
                    })
                    ->indicator('ئاستی کۆگا'),

                // فلتەری ڕێژەی قازانج
                SelectFilter::make('profit_margin_filter')
                    ->label('ڕێژەی قازانج')
                    ->options([
                        'high' => 'قازانجی زۆر (> ٢٠٪)',
                        'medium' => 'قازانجی مامناوەند (١٠٪ - ٢٠٪)',
                        'low' => 'قازانجی کەم (< ١٠٪)',
                        'loss' => 'زیان (نرخی کڕین > نرخی فرۆشتن)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'high' => $query->whereRaw('((current_price - purchase_price) / purchase_price) * 100 > 20'),
                            'medium' => $query->whereRaw('((current_price - purchase_price) / purchase_price) * 100 BETWEEN 10 AND 20'),
                            'low' => $query->whereRaw('((current_price - purchase_price) / purchase_price) * 100 < 10')
                                ->whereRaw('current_price > purchase_price'),
                            'loss' => $query->whereRaw('current_price < purchase_price'),
                            default => $query,
                        };
                    }),

                // فلتەری کاتیگۆریە چالاکەکان (ئەوانەی کۆگایان تێدایە)
                TernaryFilter::make('has_stock')
                    ->label('کۆگا')
                    ->placeholder('هەموو')
                    ->trueLabel('کۆگای تێدایە')
                    ->falseLabel('کۆگای تیا نییە')
                    ->queries(
                        true: fn ($query) => $query->where('stock_liters', '>', 0),
                        false: fn ($query) => $query->where('stock_liters', '<=', 0),
                    )
                    ->indicator('کۆگا'),

                // فلتەری بەرواری دروستبوون
                Filter::make('created_at')
                    ->label('بەرواری دروستبوون')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('until')
                            ->label('تا')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('created_at', '<=', $data['until'])
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
            ])

            // ڕێکخستنی فلتەرەکان
            ->filtersLayout(FiltersLayout::Modal) // یان FiltersLayout::AboveContent
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
                        ->modalHeading('سڕینەوەی کاتیگۆری')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کاتیگۆرییە؟')
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
                        ->modalHeading('سڕینەوەی کاتیگۆرییە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کاتیگۆریانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateHeading('هیچ کاتیگۆرییەک نییە')
            ->emptyStateDescription('یەکەم کاتیگۆری دروست بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی کاتیگۆری')
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
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
