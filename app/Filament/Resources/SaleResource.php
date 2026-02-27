<?php
// app/Filament/Resources/SaleResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use app\Models\Category;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'کڕین و فرۆشتن';
    protected static ?string $modelLabel = 'فرۆشتن';
    protected static ?string $pluralModelLabel = 'فرۆشتنەکان';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری فرۆشتن')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('کاتیگۆری')
                            ->relationship('category', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('price_per_liter', Category::find($state)?->current_price ?? 0)
                            ),
                        Forms\Components\TextInput::make('liters')
                            ->label('بڕ (لیتر)')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                $set('total_price', $state * $get('price_per_liter'))
                            ),
                        Forms\Components\TextInput::make('price_per_liter')
                            ->label('نرخی لیترێک')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                $set('total_price', $get('liters') * $state)
                            ),
                        Forms\Components\TextInput::make('total_price')
                            ->label('کۆی گشتی')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled(),
                        Forms\Components\DatePicker::make('sale_date')
                            ->label('ڕێکەوتی فرۆشتن')
                            ->required()
                            ->default(now()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('کاتیگۆری')
                    ->sortable(),
                Tables\Columns\TextColumn::make('liters')
                    ->label('بڕ')
                    ->suffix(' لیتر')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_liter')
                    ->label('نرخی لیترێک')
                    ->money('IQD'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('کۆی گشتی')
                    ->money('IQD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('ڕێکەوت')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('sale_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
        ];
    }
}
