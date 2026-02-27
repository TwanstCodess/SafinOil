<?php
// app/Filament/Resources/CategoryResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                            ->label('ناو')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('جۆر')
                            ->options([
                                'fuel' => 'بەنزین',
                                'oil' => 'ڕۆن',
                                'gas' => 'گاز',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('current_price')
                            ->label('نرخی فرۆشتن (لیترێک)')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('نرخی کڕین (لیترێک)')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('stock_liters')
                            ->label('بڕی کۆگا')
                            ->numeric()
                            ->required()
                            ->suffix('لیتر'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('ناو')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('جۆر')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fuel' => 'warning',
                        'oil' => 'success',
                        'gas' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fuel' => 'بەنزین',
                        'oil' => 'ڕۆن',
                        'gas' => 'گاز',
                    }),
                Tables\Columns\TextColumn::make('current_price')
                    ->label('نرخی فرۆشتن')
                    ->money('IQD'),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('نرخی کڕین')
                    ->money('IQD'),
                Tables\Columns\TextColumn::make('stock_liters')
                    ->label('کۆگا')
                    ->suffix(' لیتر'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
