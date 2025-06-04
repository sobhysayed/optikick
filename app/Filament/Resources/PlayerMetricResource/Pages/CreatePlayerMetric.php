<?php

namespace App\Filament\Resources\PlayerMetricResource\Pages;

use App\Filament\Resources\PlayerMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayerMetric extends CreateRecord
{
    protected static string $resource = PlayerMetricResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_at'] = now();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
