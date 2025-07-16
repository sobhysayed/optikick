<?php

namespace App\Filament\Resources\HealthInfoCardResource\Pages;

use App\Filament\Resources\HealthInfoCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHealthInfoCards extends ListRecords
{
    protected static string $resource = HealthInfoCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
