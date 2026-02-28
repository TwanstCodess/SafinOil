<?php
// app/Filament/Resources/UserResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی سیستەم';
    protected static ?string $modelLabel = 'بەکارهێنەر';
    protected static ?string $pluralModelLabel = 'بەکارهێنەران';
    protected static ?string $navigationLabel = 'بەکارهێنەران';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری بەکارهێنەر')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناوی تەواو')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-user'),
                        Forms\Components\TextInput::make('email')
                            ->label('ئیمەیڵ')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-envelope'),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('ڕێکەوتی پشتڕاستکردنەوە')
                            ->default(now()),
                        Forms\Components\Toggle::make('is_admin')
                            ->label('بەڕێوەبەرە؟')
                            ->default(false)
                            ->helperText('بەکارهێنەرانی بەڕێوەبەر دەستڕاگەیشتنی تەواویان هەیە'),
                        Forms\Components\TextInput::make('password')
                            ->label('ووشەی نهێنی')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->confirmed(),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('دووبارە ووشەی نهێنی')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-lock-closed'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ژمارە')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('ناو')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('email')
                    ->label('ئیمەیڵ')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('ئیمەیڵ کۆپی کرا')
                    ->copyMessageDuration(1500),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('بەڕێوەبەر')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('پشتڕاستکراوە')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('بەرواری دروستبوون')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('دوایین نوێکردنەوە')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // **فلتەرە پڕۆفیشناڵەکان**
            ->filters([
                // فلتەری ناو
                Filter::make('name')
                    ->label('ناوی بەکارهێنەر')
                    ->form([
                        TextInput::make('name_search')
                            ->label('ناو')
                            ->placeholder('ناوی بەکارهێنەر ...')
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['name_search'], fn ($q) => $q->where('name', 'LIKE', '%' . $data['name_search'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['name_search'] ?? null) {
                            return 'ناو: ' . $data['name_search'];
                        }
                        return null;
                    }),

                // فلتەری ئیمەیڵ
                Filter::make('email')
                    ->label('ئیمەیڵ')
                    ->form([
                        TextInput::make('email_search')
                            ->label('ئیمەیڵ')
                            ->placeholder('user@example.com')
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['email_search'], fn ($q) => $q->where('email', 'LIKE', '%' . $data['email_search'] . '%'));
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['email_search'] ?? null) {
                            return 'ئیمەیڵ: ' . $data['email_search'];
                        }
                        return null;
                    }),

                // فلتەری ڕۆڵ
                SelectFilter::make('is_admin')
                    ->label('ڕۆڵ')
                    ->options([
                        '1' => '👑 بەڕێوەبەر',
                        '0' => '👤 بەکارهێنەر',
                    ])
                    ->multiple()
                    ->indicator('ڕۆڵ')
                    ->placeholder('هەموو ڕۆڵەکان')
                    ->columnSpan(1),

                // فلتەری دۆخی پشتڕاستکردنەوە
                SelectFilter::make('verification_status')
                    ->label('دۆخی پشتڕاستکردنەوە')
                    ->options([
                        'verified' => '✅ پشتڕاستکراوە',
                        'unverified' => '❌ پشتڕاستنەکراوە',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'verified' => $query->whereNotNull('email_verified_at'),
                            'unverified' => $query->whereNull('email_verified_at'),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'verified' => '✅ پشتڕاستکراوە',
                            'unverified' => '❌ پشتڕاستنەکراوە',
                            default => null,
                        };
                    }),

                // فلتەری بەرواری پشتڕاستکردنەوە
                Filter::make('email_verified_at')
                    ->label('بەرواری پشتڕاستکردنەوە')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('until')
                            ->label('تا')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('email_verified_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('email_verified_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'لە ' . \Carbon\Carbon::parse($data['from'])->format('Y/m/d');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'تا ' . \Carbon\Carbon::parse($data['until'])->format('Y/m/d');
                        }
                        return $indicators ? 'بەرواری پشتڕاستکردنەوە: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری بەرواری دروستبوون
                Filter::make('created_at')
                    ->label('بەرواری دروستبوون')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('until')
                            ->label('تا')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'لە ' . \Carbon\Carbon::parse($data['from'])->format('Y/m/d');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'تا ' . \Carbon\Carbon::parse($data['until'])->format('Y/m/d');
                        }
                        return $indicators ? 'بەرواری دروستبوون: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری بەکارهێنەرانی ئەمڕۆ
                Filter::make('today')
                    ->label('بەکارهێنەرانی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری بەکارهێنەرانی ئەم هەفتەیە
                Filter::make('this_week')
                    ->label('بەکارهێنەرانی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری بەکارهێنەرانی ئەم مانگە
                Filter::make('this_month')
                    ->label('بەکارهێنەرانی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year))
                    ->indicator('ئەم مانگە'),

                // فلتەری بەکارهێنەرانی ئەمساڵ
                Filter::make('this_year')
                    ->label('بەکارهێنەرانی ئەمساڵ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereYear('created_at', now()->year))
                    ->indicator('ئەمساڵ'),

                // فلتەری دوایین چالاکی
                Filter::make('recently_active')
                    ->label('چالاکی دوایین ٢٤ کاتژمێر')
                    ->toggle()
                    ->query(fn ($query) => $query->where('updated_at', '>=', now()->subDay()))
                    ->indicator('٢٤ کاتژمێری ڕابردوو'),
            ])

            // ڕێکخستنی فلتەرەکان
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->filtersFormWidth('lg')
            ->persistFiltersInSession()

            // دوگمەی فلتەر
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('فلتەری پێشکەوتوو')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
                    ->size('sm')
            )

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('بینین')
                        ->icon('heroicon-m-eye')
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->label('دەستکاری')
                        ->icon('heroicon-m-pencil')
                        ->color('warning'),

                    Action::make('change_password')
                        ->label('گۆڕینی ووشەی نهێنی')
                        ->icon('heroicon-m-key')
                        ->color(Color::Orange)
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label('ووشەی نهێنی نوێ')
                                ->password()
                                ->required()
                                ->confirmed(),
                            Forms\Components\TextInput::make('password_confirmation')
                                ->label('دووبارە ووشەی نهێنی نوێ')
                                ->password()
                                ->required(),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'password' => Hash::make($data['password']),
                            ]);

                            Notification::make()
                                ->title('ووشەی نهێنی بە سەرکەوتوویی گۆڕدرا')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('گۆڕینی ووشەی نهێنی')
                        ->modalIcon('heroicon-o-key')
                        ->modalSubmitActionLabel('گۆڕین'),

                    Action::make('verify_email')
                        ->label('پشتڕاستکردنەوە')
                        ->icon('heroicon-m-check-badge')
                        ->color(Color::Green)
                        ->visible(fn (User $record): bool => is_null($record->email_verified_at))
                        ->action(function (User $record): void {
                            $record->update([
                                'email_verified_at' => now(),
                            ]);

                            Notification::make()
                                ->title('ئیمەیڵ پشتڕاست کرایەوە')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('سڕینەوە')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->modalHeading('سڕینەوەی بەکارهێنەر')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم بەکارهێنەرە؟')
                        ->modalSubmitActionLabel('بەڵێ، بسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ])
                ->label('کردارەکان')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->size('sm'),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان')
                        ->modalHeading('سڕینەوەی بەکارهێنەرە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم بەکارهێنەرانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),

                    Tables\Actions\BulkAction::make('verify_emails')
                        ->label('پشتڕاستکردنەوەی ئیمەیڵەکان')
                        ->icon('heroicon-m-check-badge')
                        ->color(Color::Green)
                        ->action(fn ($records) => $records->each->update(['email_verified_at' => now()]))
                        ->requiresConfirmation()
                        ->modalHeading('پشتڕاستکردنەوەی ئیمەیڵەکان')
                        ->modalDescription('دڵنیای لە پشتڕاستکردنەوەی ئیمەیڵی ئەم بەکارهێنەرانە؟')
                        ->modalSubmitActionLabel('بەڵێ، پشتڕاستی بکەوە')
                        ->modalCancelActionLabel('نەخێر')
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('make_admin')
                        ->label('کردن بە بەڕێوەبەر')
                        ->icon('heroicon-m-shield-check')
                        ->color(Color::Purple)
                        ->action(fn ($records) => $records->each->update(['is_admin' => true]))
                        ->requiresConfirmation()
                        ->modalHeading('کردن بە بەڕێوەبەر')
                        ->modalDescription('دڵنیای لە کردنی ئەم بەکارهێنەرانە بە بەڕێوەبەر؟')
                        ->modalSubmitActionLabel('بەڵێ، بیکە بە بەڕێوەبەر')
                        ->modalCancelActionLabel('نەخێر')
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('remove_admin')
                        ->label('لابردنی ڕۆڵی بەڕێوەبەر')
                        ->icon('heroicon-m-user')
                        ->color(Color::Gray)
                        ->action(fn ($records) => $records->each->update(['is_admin' => false]))
                        ->requiresConfirmation()
                        ->modalHeading('لابردنی ڕۆڵی بەڕێوەبەر')
                        ->modalDescription('دڵنیای لە لابردنی ڕۆڵی بەڕێوەبەری ئەم بەکارهێنەرانە؟')
                        ->modalSubmitActionLabel('بەڵێ، لایبە')
                        ->modalCancelActionLabel('نەخێر')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('هیچ بەکارهێنەرێک نییە')
            ->emptyStateDescription('یەکەم بەکارهێنەر دروست بکە')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی بەکارهێنەر')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
