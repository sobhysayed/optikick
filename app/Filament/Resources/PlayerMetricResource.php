<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerMetricResource\Pages;
use App\Models\PlayerMetric;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\User;

class PlayerMetricResource extends Resource
{
    protected static ?string $model = PlayerMetric::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('player_id')
                ->label('Player')
                ->relationship(
                    name: 'player',
                    titleAttribute: 'email',
                    modifyQueryUsing: fn ($query) => $query->where('role', 'player')
                        ->select(['id', 'email', 'name'])
                )
                ->searchable(['email', 'name'])
                ->getSearchResultsUsing(
                    fn (string $search) => User::where('role', 'player')
                        ->where('status', 'active')
                        ->where(fn ($query) => $query
                            ->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                        )
                        ->limit(50)
                        ->get()
                        ->map(fn ($user) => ['id' => $user->id, 'label' => $user->email . ' - ' . $user->name])
                )
                ->preload()
                ->required(),
            Forms\Components\DateTimePicker::make('recorded_at')
                ->required()
                ->default(now()),
            Forms\Components\TextInput::make('resting_hr')
                ->required()
                ->numeric()
                ->minValue(40)
                ->maxValue(200),
            Forms\Components\TextInput::make('max_hr')
                ->required()
                ->numeric()
                ->minValue(100)
                ->maxValue(220),
            Forms\Components\TextInput::make('hrv')
                ->required()
                ->numeric()
                ->minValue(0)
                ->maxValue(100),
            Forms\Components\TextInput::make('vo2_max')
                ->required()
                ->numeric()
                ->minValue(20)
                ->maxValue(90),
            Forms\Components\TextInput::make('weight')
                ->required()
                ->numeric()
                ->minValue(30)
                ->maxValue(150),
            Forms\Components\TextInput::make('reaction_time')
                ->required()
                ->numeric()
                ->minValue(100)
                ->maxValue(1000),
            
            // Additional optional metrics
            Forms\Components\TextInput::make('match_consistency')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),
            Forms\Components\TextInput::make('minutes_played')
                ->numeric()
                ->minValue(0),
            Forms\Components\TextInput::make('training_hours')
                ->numeric()
                ->minValue(0)
                ->maxValue(168),
            Forms\Components\TextInput::make('injury_frequency')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),
            Forms\Components\TextInput::make('recovery_time')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),
            Forms\Components\TextInput::make('fatigue_score')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),
            Forms\Components\TextInput::make('injury_risk')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),
            Forms\Components\TextInput::make('readiness_score')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('player.email'),
                Tables\Columns\TextColumn::make('recorded_at')
                    ->dateTime(),
            Tables\Columns\TextColumn::make('resting_hr'),
            Tables\Columns\TextColumn::make('max_hr'),
            Tables\Columns\TextColumn::make('hrv'),
            Tables\Columns\TextColumn::make('vo2_max'),
            Tables\Columns\TextColumn::make('weight'),
            Tables\Columns\TextColumn::make('reaction_time'),
            Tables\Columns\TextColumn::make('match_consistency')
                ->suffix('%'),
            Tables\Columns\TextColumn::make('minutes_played'),
            Tables\Columns\TextColumn::make('training_hours'),
            Tables\Columns\TextColumn::make('injury_frequency')
                ->suffix('%'),
            Tables\Columns\TextColumn::make('recovery_score')
                ->suffix('%'),
            Tables\Columns\TextColumn::make('fatigue_score')
                ->suffix('%'),
            Tables\Columns\TextColumn::make('injury_risk')
                ->suffix('%'),
            Tables\Columns\TextColumn::make('readiness_score')
                ->suffix('%'),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime(),
        ])
        ->actions([
            Tables\Actions\EditAction::make()
                ->visible(fn () => auth()->user()->role === 'admin'),
            Tables\Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->role === 'admin'),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->role === 'admin'),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlayerMetrics::route('/'),
            'create' => Pages\CreatePlayerMetric::route('/create'),
            'edit' => Pages\EditPlayerMetric::route('/{record}/edit'),
        ];
    }
}
