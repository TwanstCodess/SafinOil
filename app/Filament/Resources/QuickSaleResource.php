<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuickSaleResource\Pages;
use App\Filament\Resources\QuickSaleResource\RelationManagers;
use App\Models\QuickSale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuickSaleResource extends Resource
{
    protected static ?string $model = QuickSale::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('sale_date')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('categories_data')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('initial_readings')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('final_readings')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('sold_data')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('differences')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('closed_by')
                    ->numeric(),
                Forms\Components\TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
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
            'index' => Pages\ListQuickSales::route('/'),
            'create' => Pages\CreateQuickSale::route('/create'),
            'edit' => Pages\EditQuickSale::route('/{record}/edit'),
        ];
    }
}
