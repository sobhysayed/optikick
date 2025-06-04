<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Player\AssessmentRequest;
use App\Models\AssessmentRequest as Assessment;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;

class PlayerController extends BaseController
{
    public function getDashboard(): JsonResponse
    {
        try {
            $player = auth()->user();
            $latestMetrics = $player->metrics()->latest()->first();

            return $this->successResponse([
                'metrics' => [
                    'resting_hr' => [
                        'value' => $latestMetrics->resting_hr ?? null,
                        'unit' => 'bpm',
                        'time' => $latestMetrics ? $latestMetrics->created_at->format('g:i a') : null
                    ],
                    'max_hr' => [
                        'value' => $latestMetrics->max_hr ?? null,
                        'unit' => 'bpm',
                        'time' => $latestMetrics ? $latestMetrics->created_at->format('g:i a') : null
                    ],
                    'hrv' => [
                        'value' => $latestMetrics->hrv ?? null,
                        'unit' => 'ms',
                        'time' => $latestMetrics ? $latestMetrics->created_at->format('g:i a') : null
                    ],
                    'vo2_max' => [
                        'value' => $latestMetrics->vo2_max ?? null,
                        'unit' => 'ml/kg/min',
                        'time' => $latestMetrics ? $latestMetrics->created_at->format('g:i a') : null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch player metrics', [], 500);
        }
    }

    public function getProfile(): JsonResponse
    {
        try {
            $player = auth()->user();
            $birthDate = Carbon::parse($player->profile->date_of_birth);

            if (!$player || !$player->profile) {
                return $this->errorResponse('Profile not found', [], 404);
            }

            return $this->successResponse([
                'first_name' => $player->profile->first_name,
                'last_name' => $player->profile->last_name,
                'date_of_birth' => Carbon::parse($player->profile->date_of_birth)->format('d F Y') . ' (' . $birthDate->age . ')',
                'sex' => $player->profile->sex,
                'status' => $player->status,
                'position' => $player->profile->position,
                'blood_type' => $player->profile->blood_type,
                'email' => $player->email
            ]);
        } catch (\Exception $e) {
            \Log::error('Profile fetch error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch player profile', [], 500);
        }
    }

    public function getMetrics(): JsonResponse
    {
        try {
            $metrics = auth()->user()->metrics()
                ->select([
                    'id',
                    'resting_hr',
                    'max_hr',
                    'hrv',
                    'vo2_max',
                    'weight',
                    'reaction_time',
                    'created_at'
                ])
                ->latest()
                ->get()
                ->map(function ($metric) {
                    return [
                        'id' => $metric->id,
                        'resting_hr' => [
                            'value' => $metric->resting_hr,
                            'unit' => 'bpm',
                            'time' => $metric->created_at->format('g:i a')
                        ],
                        'max_hr' => [
                            'value' => $metric->max_hr,
                            'unit' => 'bpm',
                            'time' => $metric->created_at->format('g:i a')
                        ],
                        'hrv' => [
                            'value' => $metric->hrv,
                            'unit' => 'ms',
                            'time' => $metric->created_at->format('g:i a')
                        ],
                        'vo2_max' => [
                            'value' => $metric->vo2_max,
                            'unit' => 'ml/kg/min',
                            'time' => $metric->created_at->format('g:i a')
                        ],
                        'weight' => [
                            'value' => $metric->weight,
                            'unit' => 'kg',
                            'time' => $metric->created_at->format('g:i a')
                        ],
                        'reaction_time' => [
                            'value' => $metric->reaction_time,
                            'unit' => 'ms',
                            'time' => $metric->created_at->format('g:i a')
                        ]
                    ];
                });

            return $this->successResponse(['metrics' => $metrics]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch metrics', [], 500);
        }
    }

    public function requestAssessment(AssessmentRequest $request): JsonResponse
    {
        try {
            // Find available doctor first
            $doctor = User::where('role', 'doctor')->first();

            if (!$doctor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No doctor available',
                    'data' => []
                ], 404);
            }

            $assessment = Assessment::create([
                'player_id' => auth()->id(),
                'doctor_id' => $doctor->id,
                'issue_type' => $request->issue_type,
                'message' => $request->message,
                'requested_at' => Carbon::parse($request->date . ' ' . $request->hour),
                'status' => 'pending'
            ]);

            // Create notification for the assigned doctor
            try {
                $doctor->notifications()->create([
                    'user_id' => $doctor->id,
                    'type' => 'assessment_request',
                    'title' => 'New Assessment Request',
                    'body' => auth()->user()->profile->first_name . ' has requested an assessment.',
                    'sender_id' => auth()->id(),
                    'related_assessment_id' => $assessment->id,
                    'read_at' => null,
                    'is_pinned' => false
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create doctor notification: ' . $e->getMessage());
                \Log::error('Error details: ' . $e->getTraceAsString());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Assessment request created successfully',
                'data' => [
                    'assessment' => $assessment
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Assessment request error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create assessment request',
                'data' => []
            ], 500);
        }
    }


    public function getTrainingProgram(): JsonResponse
    {
        try {
            $program = auth()->user()->trainingPrograms()
                ->latest()
                ->first();

            if (!$program) {
                return $this->errorResponse('No training program found', [], 404);
            }

            return $this->successResponse([
                'program' => [
                    'id' => $program->id,
                    'focus_area' => $program->focus_area,
                    'exercises' => $program->exercises,
                    'status' => $program->status,
                    'created_at' => $program->created_at,
                    'updated_at' => $program->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Training program fetch error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch training program', [], 500);
        }
    }
}
