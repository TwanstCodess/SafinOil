<?php
// app/Filament/Resources/FuelPurchaseResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\FuelPurchaseResource\Pages;
use App\Models\FuelPurchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use app\Models\Category;

class FuelPurchaseResource extends Resource
{
    protected static ?string $model = FuelPurchase::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'کڕین و فرۆشتن';
    protected static ?string $modelLabel = 'کڕینی بەنزین';
    protected static ?string $pluralModelLabel = 'کڕینی بەنزین';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری کڕین')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('کاتیگۆری')
                            ->relationship('category', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('price_per_liter', Category::find($state)?->purchase_price ?? 0)
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
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('ڕێکەوتی کڕین')
                            ->required()
                            ->default(now()),
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
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('ڕێکەوت')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('purchase_date', 'desc')
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
            'index' => Pages\ListFuelPurchases::route('/'),
            'create' => Pages\CreateFuelPurchase::route('/create'),
        ];
    }
}
