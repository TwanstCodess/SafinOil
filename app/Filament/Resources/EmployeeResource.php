<?php
// app/Filament/Resources/EmployeeResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی کارمەندان';
    protected static ?string $modelLabel = 'کارمەند';
    protected static ?string $pluralModelLabel = 'کارمەندان';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری کارمەند')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناو')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('position')
                            ->label('پلە')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('ژمارە مۆبایل')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('salary')
                            ->label('مووچە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('hire_date')
                            ->label('ڕێکەوتی دەستبەکاربوون')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('چالاکە')
                            ->default(true),
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
                Tables\Columns\TextColumn::make('position')
                    ->label('پلە'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('مۆبایل'),
                Tables\Columns\TextColumn::make('salary')
                    ->label('مووچە')
                    ->money('IQD'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('چالاکە')
                    ->boolean(),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
