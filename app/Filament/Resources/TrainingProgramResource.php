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
                Forms\Components\Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(65535),
                Forms\Components\Select::make('type')
                    ->options([
                        'strength' => 'Strength',
                        'cardio' => 'Cardio',
                        'flexibility' => 'Flexibility',
                        'recovery' => 'Recovery',
                        'custom' => 'Custom'
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled'
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Toggle::make('is_ai_generated')
                    ->required(),
                Forms\Components\Toggle::make('is_approved')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::where('role', 'player'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.position')
                    ->label('Position'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('generateProgram')
                    ->label('Generate Program')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        try {
                            $metrics = $record->metrics()->latest()->first();
                            
                            if (!$metrics) {
                                Notification::make()
                                    ->danger()
                                    ->title('No metrics found')
                                    ->body('Player must add metrics first')
                                    ->send();
                                return;
                            }
                            
                            $aiService = new AIModelService();
                            $doctor = User::where('role', 'doctor')->first();
                            
                            if (!$doctor) {
                                Notification::make()
                                    ->danger()
                                    ->title('No doctor found')
                                    ->body('System requires at least one doctor')
                                    ->send();
                                return;
                            }
                            
                            $program = $aiService->generateTrainingProgram($record, $metrics, $doctor);
                            
                            $doctor->notifications()->create([
                                'user_id' => $doctor->id,
                                'type' => 'training_program',
                                'title' => 'New Training Program Generated',
                                'body' => "A new training program has been generated for player {$record->name}",
                                'sender_id' => auth()->id(),
                                'related_program_id' => $program->id,
                                'read_at' => null,
                                'is_pinned' => false
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Success')
                                ->body('Training program generated successfully')
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Training program generation error: ' . $e->getMessage());
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to generate training program')
                                ->send();
                        }
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingPrograms::route('/'),
        ];
    }
}
