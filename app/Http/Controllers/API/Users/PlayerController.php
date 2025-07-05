<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Player\AssessmentRequest;
use App\Models\AssessmentRequest as Assessment;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\MetricAnalysisService;

class PlayerController extends BaseController
{
    protected $metricAnalysisService;

    public function __construct()
    {
        $this->metricAnalysisService = app(MetricAnalysisService::class);
    }

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
            ],
                "Dashboard metrics fetched successfully");
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
            ],
                'Player profile fetched successfully');
        } catch (\Exception $e) {
            \Log::error('Profile fetch error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch player profile', [], 500);
        }
    }

    public function getMetrics(): JsonResponse
    {
        try {
            $metric = auth()->user()->metrics()
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
                ->first();

            if (!$metric) {
                return $this->successResponse(['metric' => null], 'No metrics found');
            }

            $formatted = [
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

            return $this->successResponse(['metric' => $formatted], 'Latest player metric fetched successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch metrics', [], 500);
        }
    }

    public function requestAssessment(Request $request): JsonResponse
    {
        try {
            $player = auth()->user();

            if ($player->role !== 'player') {
                return response()->json(['message' => 'Only players can request assessments.'], 403);
            }

            // 1. Validate input
            $data = $request->validate([
                'date' => 'required|date',
                'hour' => 'required|date_format:H:i',
                'issue_type' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
            ]);

            $requestedAt = Carbon::parse("{$data['date']} {$data['hour']}");

            // 2. Prevent multiple requests in 24 hours
            $recentRequest = \App\Models\AssessmentRequest::where('player_id', $player->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if ($recentRequest) {
                return response()->json(['message' => 'You can only request one assessment every 24 hours.'], 429);
            }

            // 4. Check if the player already has an assessment at that exact time
            $duplicateTime = Assessment::where('player_id', $player->id)
                ->where('requested_at', $requestedAt)
                ->exists();

            if ($duplicateTime) {
                return $this->errorResponse('You already have an assessment scheduled at this time.', [], 409);
            }

            // 5. Find a doctor who is not already booked at that time
            $doctor = User::where('role', 'doctor')
                ->whereDoesntHave('assessments', function ($query) use ($requestedAt) {
                    $query->whereIn('status', ['pending', 'approved', 'postponed'])
                        ->where('requested_at', $requestedAt);
                })
                ->first();

            if (!$doctor) {
                return $this->errorResponse('No doctor is available at the requested time.', [], 409);
            }

            $assessment = Assessment::create([
                'player_id'    => $player->id,
                'doctor_id'    => $doctor->id,
                'issue_type'   => $data['issue_type'],
                'message'      => $data['message'],
                'requested_at' => $requestedAt,
                'status'       => 'pending',
            ]);

            $doctor->notifications()->create([
                'user_id' => $doctor->id,
                'type' => 'assessment',
                'title' => 'New Assessment Request',
                'body' => optional($player->profile)->first_name . ' has requested an assessment.',
                'sender_id' => $player->id,
                'related_assessment_id' => $assessment->id,
            ]);

            return response()->json([
                'message' => 'Assessment created successfully.',
                'data' => [
                    'id' => $assessment->id,
                    'doctor' => $doctor->name,
                    'requested_at' => $assessment->requested_at->toDateTimeString(),
                    'status' => $assessment->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating assessment: ' . $e->getMessage());
            return response()->json(['message' => 'Something went wrong.'], 500);
        }
    }


    public function getTrainingProgram(): JsonResponse
    {
        try {
            $user = auth()->user();

            $program = $user->trainingPrograms()
                ->where('status', 'approved')
                ->latest()
                ->first();

            if (!$program) {
                return $this->errorResponse(
                    "Your new training program is being prepared. You'll receive a notification once it's ready",
                    [],
                    404
                );
            }

            $exerciseList = $program->exercises['program'] ?? [];

            return $this->successResponse([
                'program' => [
                    'id' => $program->id,
                    'focus_area' => $program->focus_area,
                    'exercises' => $exerciseList,
                    'status' => $program->status,
                    'created_at' => $program->created_at,
                    'updated_at' => $program->updated_at
                ]
            ], 'Training program fetched successfully');

        } catch (\Exception $e) {
            \Log::error('Training program fetch error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch training program', [], 500);
        }
    }

    public function getMetricDetail(Request $request, string $metricType): JsonResponse
    {
        try {
            $player = auth()->user();
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player', [], 400);
            }

            $period = $request->input('period', 'D'); // D=Daily, W=Weekly, M=Monthly, 6M=6 Months
            $startDate = match($period) {
                'D' => now()->subDays(7),
                'W' => now()->subWeeks(4),
                'M' => now()->subMonths(1),
                '6M' => now()->subMonths(6),
                default => now()->subDays(7)
            };

            $metrics = $player->metrics()
                ->select(['id', $metricType, 'created_at'])
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($metric) use ($metricType) {
                    return [
                        'date' => $metric->created_at->format('D'),
                        'value' => $metric->$metricType,
                    ];
                });

            $analysis = $this->metricAnalysisService->analyzeMetric(
                $metrics->pluck('value')->toArray(),
                $metricType
            );

            return $this->successResponse(
                [
                    'player' => [
                        'id' => $player->id,
                        'name' => $player->name
                    ],
                    'metric_type' => $metricType,
                    'period' => $period,
                    'graph_data' => $metrics,
                    'highlights' => $analysis['highlights'] ?? [],
                    'trend' => $analysis['trend'] ?? null
                ],
                'Metric insights generated based on recent performance data.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch metric detail', [], 500);
        }
    }
}
