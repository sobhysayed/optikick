<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Filament\Resources\TrainingProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingPrograms extends ListRecords
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('managePlayers')
                ->label('Manage Players')
                ->icon('heroicon-o-users')
                ->url(route('filament.admin.resources.training-programs.players'))
                ->visible(fn () => auth()->user()->role === 'admin'),
        ];
    }
}
