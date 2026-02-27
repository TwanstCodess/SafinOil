<?php
// app/Filament/Resources/ExpenseResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'خەرجی';
    protected static ?string $pluralModelLabel = 'خەرجییەکان';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری خەرجی')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('ناونیشان')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('ڕێکەوت')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('category')
                            ->label('جۆر')
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('title')
                    ->label('ناونیشان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی پارە')
                    ->money('IQD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('جۆر'),
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('ڕێکەوت')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('expense_date', 'desc')
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
