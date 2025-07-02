<?php

namespace App\Filament\Resources\PlayerMetricsResource\Pages;

use App\Filament\Resources\PlayerMetricsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlayerMetrics extends ListRecords
{
    protected static string $resource = PlayerMetricsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}