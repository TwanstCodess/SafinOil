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
use Filament\Forms\Components\Select;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'بەرهەمەکان';
    protected static ?string $modelLabel = 'کاتیگۆری';
    protected static ?string $pluralModelLabel = 'کاتیگۆریەکان';

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

                        // ئەم Selectـە لە تەیبڵی typesـەوە داتا وەردەگرێت
                        Select::make('type_id')
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('type.name')
                    ->label('جۆر')
                    ->badge()
                    ->color(fn ($record) => $record->type?->getFilamentColor() ?? 'gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_price')
                    ->label('نرخی فرۆشتن')
                    ->money('IQD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('نرخی کڕین')
                    ->money('IQD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('stock_liters')
                    ->label('کۆگا')
                    ->suffix(' لیتر')
                    ->badge()
                    ->color(fn ($state): string =>
                        $state > 1000 ? 'success' : ($state > 100 ? 'warning' : 'danger')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('ڕێکەوتی دروستبوون')
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_id')
                    ->label('فلتەر بەپێی جۆر')
                    ->relationship('type', 'name')
                    ->multiple()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('دەستکاری'),
                Tables\Actions\DeleteAction::make()
                    ->label('سڕینەوە'),
                Tables\Actions\ViewAction::make()
                    ->label('بینین'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان'),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی کاتیگۆری'),
            ])
            ->defaultSort('name');
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
}
