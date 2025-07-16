<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Team Name')
                            ->placeholder('Enter team name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('coach_id')
                            ->label('Team Coach')
                            ->relationship('coach', 'name', fn ($query) => 
                                $query->where('role', 'coach')
                            )
                            ->searchable()
                            ->required(),
                        Forms\Components\FileUpload::make('logo')
                            ->label('Team Logo')
                            ->image()
                            ->directory('team-logos')
                            ->maxSize(2048)
                            ->imagePreviewHeight('100'),
                    ])->columns(2),

                Forms\Components\Section::make('Team Details')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Team Description')
                            ->placeholder('Enter team description')
                            ->maxLength(65535),
                        Forms\Components\TextInput::make('location')
                            ->label('Team Location')
                            ->placeholder('Enter team location')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Team Players')
                    ->schema([
                        Forms\Components\Select::make('players')
                            ->multiple()
                            ->relationship('players', 'name', fn ($query) => 
                                $query->where('role', 'player')
                                     ->where('status', 'active')
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('coach.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('players_count')
                    ->counts('players')
                    ->label('Players'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('coach')
                    ->relationship('coach', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Team')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}