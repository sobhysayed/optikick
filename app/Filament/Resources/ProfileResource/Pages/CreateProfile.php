<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateProfile extends CreateRecord
{
    protected static string $resource = ProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove status field if it exists in the form data
        unset($data['status']);
        
        // Get the user associated with this profile
        if (isset($data['user_id'])) {
            $user = User::find($data['user_id']);
            
            // Set position based on user role
            if ($user) {
                switch ($user->role) {
                    case 'coach':
                        $data['position'] = 'head_coach';
                        break;
                    case 'doctor':
                        $data['position'] = 'team_doctor';
                        break;
                }
            }
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
