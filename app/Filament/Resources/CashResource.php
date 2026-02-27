<?php
// app/Filament/Resources/CashResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CashResource\Pages;
use App\Models\Cash;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashResource extends Resource
{
    protected static ?string $model = Cash::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'قاسە';
    protected static ?string $pluralModelLabel = 'قاسە';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری قاسە')
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->label('ڕەوشتی قاسە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled()
                            ->default(0),
                        Forms\Components\TextInput::make('total_income')
                            ->label('کۆی داهات')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('total_expense')
                            ->label('کۆی خەرجی')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('last_update')
                            ->label('دوایین نوێکردنەوە')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('balance')
                    ->label('ڕەوشتی قاسە')
                    ->money('IQD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_income')
                    ->label('کۆی داهات')
                    ->money('IQD'),
                Tables\Columns\TextColumn::make('total_expense')
                    ->label('کۆی خەرجی')
                    ->money('IQD'),
                Tables\Columns\TextColumn::make('last_update')
                    ->label('دوایین نوێکردنەوە')
                    ->date(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
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
