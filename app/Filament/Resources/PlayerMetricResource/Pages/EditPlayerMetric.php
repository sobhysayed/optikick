<?php

namespace App\Filament\Resources\PlayerMetricResource\Pages;

use App\Filament\Resources\PlayerMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlayerMetric extends EditRecord
{
    protected static string $resource = PlayerMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
