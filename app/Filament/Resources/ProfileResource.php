<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\User;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'email'
                    )
                    ->preload()
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $user = \App\Models\User::find($state);
                        if ($user) {
                            if ($user->role === 'coach') {
                                $set('position', 'head coach');
                            } elseif ($user->role === 'doctor') {
                                $set('position', 'doctor');
                            }
                        }
                    }),
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date_of_birth')
                    ->required()
                    ->maxDate(now()->subYears(16))
                    ->displayFormat('d/m/Y'),
                Forms\Components\Select::make('sex')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->required(),
                Select::make('position')
                    ->options(function ($get) {
                        $user = User::find($get('user_id'));
                        if (!$user) return [];
                        
                        switch ($user->role) {
                            case 'coach':
                                return [
                                    'coach' => 'Coach',
                                    'head_coach' => 'Head Coach'
                                ];
                            case 'doctor':
                                return [
                                    'team_doctor' => 'Team Doctor'
                                ];
                            case 'player':
                                return [
                                    'striker' => 'Striker',
                                    'midfielder' => 'Midfielder',
                                    'defender' => 'Defender',
                                    'goalkeeper' => 'Goalkeeper',
                                    'forward' => 'Forward',
                                ];
                            default:
                                return [];
                        }
                    })
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'injured' => 'Injured',
                        'suspended' => 'Suspended',
                    ])
                    ->visible(function (Forms\Get $get) {
                        $user = \App\Models\User::find($get('user_id'));
                        return $user && $user->role === 'player';
                    })
                    ->default('active'),
                Forms\Components\Select::make('blood_type')
                    ->options([
                        'A+' => 'A+',
                        'A-' => 'A-',
                        'B+' => 'B+',
                        'B-' => 'B-',
                        'AB+' => 'AB+',
                        'AB-' => 'AB-',
                        'O+' => 'O+',
                        'O-' => 'O-',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Profile::query())
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sex')
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->visible(fn ($record) => $record && $record->user && $record->user->role === 'player'),
                Tables\Columns\TextColumn::make('blood_type')
                    ->sortable(),
            ])
            ->defaultSort('user.email', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->options([
                        'goalkeeper' => 'Goalkeeper',
                        'defender' => 'Defender',
                        'midfielder' => 'Midfielder',
                        'forward' => 'Forward',
                        'head coach' => 'Head Coach',
                        'doctor' => 'Doctor',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'injured' => 'Injured',
                        'suspended' => 'Suspended',
                    ])
                    ->visible(fn () => Profile::query()
                        ->whereHas('user', fn ($q) => $q->where('role', 'player'))
                        ->exists()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
            // Removed headerActions section
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'create' => Pages\CreateProfile::route('/create'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }
}
