<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Filament\Resources\TrainingProgramResource;
use Filament\Resources\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use App\Models\User;
use Filament\Tables;
use App\Services\AIModelService;
use Filament\Notifications\Notification;

class ListPlayers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = TrainingProgramResource::class;

    protected static string $view = 'filament.resources.training-program-resource.pages.list-players';

    public function table(Table $table): Table
    {
        return $table
            ->query(User::where('role', 'player'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.position')
                    ->label('Position'),
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
}