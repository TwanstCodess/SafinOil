<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashResource\Pages;
use App\Filament\Resources\CashResource\RelationManagers;
use App\Models\Cash;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashResource extends Resource
{
    protected static ?string $model = Cash::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_income')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_expense')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DatePicker::make('last_update')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('balance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_income')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_expense')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_update')
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
            'index' => Pages\ListCashes::route('/'),
            'create' => Pages\CreateCash::route('/create'),
            'edit' => Pages\EditCash::route('/{record}/edit'),
        ];
    }
}
