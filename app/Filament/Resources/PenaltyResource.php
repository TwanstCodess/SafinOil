<?php
// app/Filament/Resources/PenaltyResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\PenaltyResource\Pages;
use App\Models\Penalty;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PenaltyResource extends Resource
{
    protected static ?string $model = Penalty::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی کارمەندان';
    protected static ?string $modelLabel = 'سزا';
    protected static ?string $pluralModelLabel = 'سزاکان';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری سزا')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('کارمەند')
                            ->relationship('employee', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی سزا')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('penalty_date')
                            ->label('ڕێکەوتی سزا')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('reason')
                            ->label('هۆکار')
                            ->required()
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('کارمەند')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی سزا')
                    ->money('IQD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('هۆکار'),
                Tables\Columns\TextColumn::make('penalty_date')
                    ->label('ڕێکەوت')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('penalty_date', 'desc')
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
            'index' => Pages\ListPenalties::route('/'),
            'create' => Pages\CreatePenalty::route('/create'),
            'edit' => Pages\EditPenalty::route('/{record}/edit'),
        ];
    }
}
