<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingProgramResource\Pages;
use App\Models\TrainingProgram;
use App\Services\AIModelService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('player_id')
                    ->relationship('player', 'name')
                    ->required(),
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->required(),
                Forms\Components\TextInput::make('focus_area')
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'active' => 'Active',
                        'completed' => 'Completed'
                    ])
                    ->required(),
                Forms\Components\Toggle::make('ai_generated')
                    ->label('AI Generated'),
                Forms\Components\DateTimePicker::make('approved_at')
                    ->label('Approved At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('player.name')
                    ->label('Player')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('focus_area')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'active' => 'info',
                        'completed' => 'secondary',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('ai_generated')
                    ->boolean()
                    ->label('AI Generated'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'active' => 'Active',
                        'completed' => 'Completed'
                    ]),
                Tables\Filters\TernaryFilter::make('ai_generated')
                    ->label('AI Generated'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => auth()->user()->role === 'admin' || auth()->user()->role === 'doctor'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->role === 'admin'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingPrograms::route('/'),
            'players' => Pages\ListPlayers::route('/players'),
        ];
    }
}
