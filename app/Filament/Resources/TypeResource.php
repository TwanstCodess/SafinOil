<?php
// app/Filament/Resources/TypeResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\TypeResource\Pages;
use App\Models\Type;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TypeResource extends Resource
{
    protected static ?string $model = Type::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی سیستەم';
    protected static ?string $modelLabel = 'جۆر';
    protected static ?string $pluralModelLabel = 'جۆرەکان';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری جۆر')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناو')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('key')
                            ->label('کلیل (بە ئینگلیزی)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('بۆ ناسینەوەی جۆر لە کۆددا بەکار دێت'),
                        Forms\Components\Select::make('color')
                            ->label('ڕەنگ')
                            ->options([
                                'warning' => 'زەرد',
                                'success' => 'سەوز',
                                'info' => 'شین',
                                'danger' => 'سور',
                                'gray' => 'ڕەساسی',
                            ])
                            ->default('gray'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('ناو')
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')
                    ->label('کلیل')
                    ->badge()
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('ڕەنگ'),
                Tables\Columns\TextColumn::make('categories_count')
                    ->label('ژمارەی کاتیگۆری')
                    ->counts('categories'),
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
            'index' => Pages\ListTypes::route('/'),
            'create' => Pages\CreateType::route('/create'),
            'edit' => Pages\EditType::route('/{record}/edit'),
        ];
    }
}
