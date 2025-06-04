<?php

namespace App\Filament\Resources\AssessmentRequestResource\Pages;

use App\Filament\Resources\AssessmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentRequest extends EditRecord
{
    protected static string $resource = AssessmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
