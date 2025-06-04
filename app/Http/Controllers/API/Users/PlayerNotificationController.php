<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;

class PlayerNotificationController extends BaseController
{
    public function __construct()
    {
    }

    public function getNotifications()
    {
        try {
            $notifications = auth()->user()
                ->notifications()
                ->whereIn('type', [
                    'health_status',
                    'training_program',
                    'assessment_approved',
                    'assessment_postponed'
                ])
                ->with(['relatedAssessment', 'relatedTrainingProgram'])
                ->latest()
                ->paginate(20);

            return $this->successResponse([
                'notifications' => $notifications,
                'unread_count' => auth()->user()
                    ->unreadNotifications()
                    ->whereIn('type', [
                        'health_status',
                        'training_program',
                        'assessment_approved',
                        'assessment_postponed'
                    ])
                    ->count()
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch notifications', [], 500);
        }
    }
}