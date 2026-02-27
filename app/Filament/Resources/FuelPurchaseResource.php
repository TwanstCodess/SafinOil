<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FuelPurchaseResource\Pages;
use App\Filament\Resources\FuelPurchaseResource\RelationManagers;
use App\Models\FuelPurchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FuelPurchaseResource extends Resource
{
    protected static ?string $model = FuelPurchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('liters')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('price_per_liter')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('purchase_date')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('liters')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_liter')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListFuelPurchases::route('/'),
            'create' => Pages\CreateFuelPurchase::route('/create'),
            'edit' => Pages\EditFuelPurchase::route('/{record}/edit'),
        ];
    }
}
