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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.position')
                    ->label('Position'),
                Tables\Columns\TextColumn::make('latestTrainingProgram.status')
                    ->label('Current Program Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'active' => 'info',
                        'completed' => 'secondary',
                        default => 'gray',
                    })
                    ->default('No Program'),
                Tables\Columns\TextColumn::make('latestTrainingProgram.created_at')
                    ->label('Program Created')
                    ->dateTime()
                    ->default('N/A'),
                Tables\Columns\IconColumn::make('hasMetrics')
                    ->label('Has Metrics')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->metrics()->exists()),
            ])
            ->actions([
                Tables\Actions\Action::make('generateProgram')
                    ->label('Generate Program')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Training Program')
                    ->modalDescription('This will generate an AI-powered training program for the player. The program will be set to pending status and the doctor will be notified for review.')
                    ->modalSubmitActionLabel('Generate Program')
                    ->action(function (User $record) {
                        try {
                            $metrics = $record->metrics()->latest()->first();

                            if (!$metrics) {
                                Notification::make()
                                    ->danger()
                                    ->title('No metrics found')
                                    ->body('Player must add metrics first before generating a training program.')
                                    ->send();
                                return;
                            }

                            $aiService = new AIModelService();
                            $doctor = User::where('role', 'doctor')->first();

                            if (!$doctor) {
                                Notification::make()
                                    ->danger()
                                    ->title('No doctor found')
                                    ->body('System requires at least one doctor to review training programs.')
                                    ->send();
                                return;
                            }

                            $program = $aiService->generateTrainingProgram($record, $metrics, $doctor);

                            Notification::make()
                                ->success()
                                ->title('Training Program Generated')
                                ->body("Training program has been generated for {$record->name} and sent to doctor for review.")
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to generate training program: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn () => auth()->user()->role === 'admin'),
                Tables\Actions\Action::make('viewProgram')
                    ->label('View Program')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record) => route('filament.admin.resources.training-programs.index', ['tableFilters[player_id][value]' => $record->id]))
                    ->visible(fn (User $record) => $record->trainingPrograms()->exists()),
            ]);
    }
}
