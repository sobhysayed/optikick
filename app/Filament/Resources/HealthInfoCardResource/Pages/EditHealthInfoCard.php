<?php

namespace App\Filament\Resources\HealthInfoCardResource\Pages;

use App\Filament\Resources\HealthInfoCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHealthInfoCard extends EditRecord
{
    protected static string $resource = HealthInfoCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
