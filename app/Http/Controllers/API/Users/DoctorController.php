<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\TrainingProgram;
use App\Models\AssessmentRequest;
use App\Models\PlayerMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\MetricAnalysisService;
use App\Services\AIModelService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;

class DoctorController extends BaseController
{
    use AuthorizesRequests;

    private $metricAnalysisService;

    public function __construct(MetricAnalysisService $metricAnalysisService)
    {
        $this->metricAnalysisService = $metricAnalysisService;
    }

    public function getDashboard(): JsonResponse
    {
        try {
            $doctor = auth()->user();

            $players = User::where('role', 'player')
                ->select('id', 'status')
                ->get();

            $statusCounts = [
                'Optimal' => 0,
                'At Risk' => 0,
                'Underperforming' => 0,
                'Recovering' => 0
            ];

            foreach ($players as $player) {
                if (isset($statusCounts[$player->status])) {
                    $statusCounts[$player->status]++;
                }
            }

            $totalPlayers = $players->count();
            $statusOverview = [];

            foreach ($statusCounts as $status => $count) {
                $percentage = $totalPlayers > 0 ? round(($count / $totalPlayers) * 100) : 0;
                $statusOverview[$status] = [
                    'percentage' => $percentage,
                    'count' => $count,
                    'label' => "$status: $percentage% ($count players)"
                ];
            }

            return $this->successResponse([
                'status_overview' => $statusOverview,
                'total_players' => $totalPlayers
            ], 'Dashboard data fetched successfully');
        } catch (\Exception $e) {
            \Log::error('Dashboard Error:', ['message' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch dashboard data', [], 500);
        }
    }

    public function getProfile(): JsonResponse
    {
        try {
            $doctor = auth()->user()->load('profile');
            $birthDate = Carbon::parse($doctor->profile->date_of_birth);

            return $this->successResponse([
                'first_name' => $doctor->profile->first_name,
                'last_name' => $doctor->profile->last_name,
                'date_of_birth' => $birthDate->format('d F Y') . ' (' . $birthDate->age . ')',
                'sex' => $doctor->profile->sex,
                'position' => $doctor->profile->position,
                'blood_type' => $doctor->profile->blood_type,
                'email' => $doctor->email
            ], 'Doctor profile fetched successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch profile data', [], 500);
        }
    }

    public function listAllPlayers(): JsonResponse
    {
        try {
            $players = User::where('role', 'player')
                ->with('profile')
                ->select('id', 'status', 'name')
                ->get()
                ->map(function ($player) {
                    return [
                        'id' => $player->id,
                        'name' => $player->name,
                        'position' => optional($player->profile)->position,
                        'status' => $player->status
                    ];
                });

            return $this->successResponse([
                'players' => $players
            ], 'Player list fetched successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to fetch player list: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch player list', [], 500);
        }
    }

    public function getPlayerProgram(User $player): JsonResponse
    {
        if (auth()->user()->role !== 'doctor') {
            return $this->errorResponse('Only doctors can view player training programs', [], 403);
        }

        try {
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
            }

            // Get the latest program (approved or not)
            $latestProgram = TrainingProgram::where('player_id', $player->id)
                ->latest()
                ->first();

            // No program at all
            if (!$latestProgram) {
                return $this->successResponse([
                    'player' => [
                        'id' => $player->id,
                        'name' => $player->name,
                        'status' => $player->status
                    ],
                    'program' => null
                ], 'No training program found');
            }

            // Use approved if latest is approved, otherwise try fallback
            if ($latestProgram->status === 'approved') {
                $program = $latestProgram;
            } else {
                // Fallback: get previous approved program
                $program = TrainingProgram::where('player_id', $player->id)
                    ->where('status', 'approved')
                    ->where('id', '<>', $latestProgram->id)
                    ->latest()
                    ->first();

                if (!$program) {
                    return $this->successResponse([
                        'player' => [
                            'id' => $player->id,
                            'name' => $player->name,
                            'status' => $player->status
                        ],
                        'program' => null
                    ], 'No approved training program available');
                }
            }

            $exerciseList = $program->exercises['program'] ?? [];

            return $this->successResponse([
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'status' => $player->status
                ],
                'program' => [
                    'id' => $program->id,
                    'focus_area' => $program->focus_area,
                    'exercises' => $exerciseList,
                    'status' => $program->status,
                    'created_at' => $program->created_at
                ]
            ], 'Training program fetched successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to fetch player program: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch player program', [], 500);
        }
    }

    public function getPlayerMetrics(User $player): JsonResponse
    {
        try {
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
            }

            $fields = [
                'resting_hr'     => 'bpm',
                'max_hr'         => 'bpm',
                'hrv'            => 'ms',
                'vo2_max'        => 'ml/kg/min',
                'weight'         => 'kg',
                'reaction_time'  => 'ms',
            ];

            $latestMetric = $player->metrics()
                ->select(array_merge(['id', 'created_at'], array_keys($fields)))
                ->latest()
                ->first();

            if (!$latestMetric) {
                return $this->successResponse([
                    'player' => [
                        'id' => $player->id,
                        'name' => $player->name,
                        'status' => $player->status
                    ],
                    'metric' => null
                ], 'No metrics found for the player.');
            }

            $metricData = ['id' => $latestMetric->id];
            foreach ($fields as $field => $unit) {
                $metricData[$field] = [
                    'value' => $latestMetric->$field,
                    'unit' => $unit,
                    'time' => $latestMetric->created_at->format('g:i a'),
                ];
            }

            return $this->successResponse([
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'status' => $player->status
                ],
                'metric' => $metricData
            ], 'Latest player metric fetched successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to fetch latest player metric', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch player metric', [], 500);
        }
    }

    public function getPlayerMetricDetail(Request $request, User $player, string $metricType): JsonResponse
    {
        try {
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
            }

            // Valid metric fields
            $validMetricTypes = [
                'resting_hr',
                'max_hr',
                'hrv',
                'vo2_max',
                'weight',
                'reaction_time',
            ];

            if (!in_array($metricType, $validMetricTypes, true)) {
                return $this->errorResponse("Invalid metric type: '$metricType'.", [], 422);
            }

            $period = $request->input('period', 'D'); // D=Daily, W=Weekly, M=Monthly, 6M=6 Months

            $startDate = match($period) {
                'D' => now()->subDays(7),
                'W' => now()->subWeeks(4),
                'M' => now()->subMonth(),
                '6M' => now()->subMonths(6),
                default => now()->subDays(7)
            };

            $metrics = $player->metrics()
                ->select(['id', $metricType, 'created_at'])
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($metric) => [
                    'date' => $metric->created_at->format('D'),
                    'value' => $metric->$metricType,
                ]);

            $analysis = $this->metricAnalysisService->analyzeMetric(
                $metrics->pluck('value')->toArray(),
                $metricType
            );

            return $this->successResponse([
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name
                ],
                'metric_type' => $metricType,
                'period' => $period,
                'graph_data' => $metrics,
                'highlights' => $analysis['highlights'] ?? [],
                'trend' => $analysis['trend'] ?? null
            ], 'Player performance data fetched successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to fetch metric detail', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch metric detail', [], 500);
        }
    }

    public function editPlayerTrainingProgram(Request $request, User $player): JsonResponse
    {
        if (auth()->user()->role !== 'doctor') {
            return $this->errorResponse('Only doctors can edit training programs', [], 403);
        }

        if ($player->role !== 'player') {
            return $this->errorResponse('Invalid player selected', [], 400);
        }

        // Get the latest training program for the player
        $program = TrainingProgram::where('player_id', $player->id)->latest()->first();

        if (!$program) {
            return $this->errorResponse('No training program found for this player', [], 404);
        }

        $validated = $request->validate([
            'focus_area' => 'sometimes|string|max:255',
            'exercises' => 'sometimes|array',
            'exercises.*' => 'string',
            'status' => 'sometimes|in:pending,approved',
        ]);

        $validated['ai_generated'] = false;

        $program->update($validated);

        $player->notifications()->create([
            'type' => 'training_program',
            'title' => 'Training Program Updated',
            'body' => 'Your training program has been updated by the doctor.',
            'sender_id' => auth()->id(),
            'related_program_id' => $program->id
        ]);

        return $this->successResponse($program->fresh(), 'Training program updated successfully');
    }

    public function getAssessmentRequests(): JsonResponse
    {
        try {
            $requests = auth()->user()->assignedAssessments()
                ->where('status', 'pending')
                ->with('player.profile')
                ->latest()
                ->get()
                ->map(function ($assessment) {
                    $dateTime = \Carbon\Carbon::parse($assessment->requested_at);
                    return [
                        'id' => $assessment->id,
                        'player_id' => $assessment->player_id,
                        'first_name' => $assessment->player->profile->first_name ?? null,
                        'last_name' => $assessment->player->profile->last_name ?? null,
                        'requested_at' => $dateTime->toDateTimeString(),
                        'message' => 'Requesting an assessment on ' . $dateTime->format('jS M \a\t g A'),
                        'status' => $assessment->status
                    ];
                });

            $message = $requests->isEmpty()
                ? 'No pending assessment requests found'
                : 'Assessment requests fetched successfully';

            return $this->successResponse(['assessments' => $requests], $message);
        } catch (\Exception $e) {
            \Log::error('Assessment fetch error:', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch assessment requests', [], 500);
        }
    }

    public function showAssessmentRequest(AssessmentRequest $assessment): JsonResponse
    {
        try {
            $dateTime = \Carbon\Carbon::parse($assessment->requested_at);

            return $this->successResponse([
                'issue_type' => $assessment->issue_type,
                'date' => $dateTime->format('jS M Y'),
                'hour' => $dateTime->format('g A'),
                'message' => $assessment->message
            ], 'Assessment request details fetched successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to fetch assessment request detail', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch assessment details', [], 500);
        }
    }

    public function approveAssessment(AssessmentRequest $assessment): JsonResponse
    {
        try {
            $doctor = auth()->user();

            // Ensure assessment is still pending
            if ($assessment->status !== 'pending') {
                return $this->errorResponse('This assessment cannot be approved.', [], 400);
            }

            // Check for double-booking
            $conflict = AssessmentRequest::where('doctor_id', $doctor->id)
                ->where('requested_at', $assessment->requested_at)
                ->where('id', '!=', $assessment->id)
                ->where('status', 'approved')
                ->exists();

            if ($conflict) {
                return $this->errorResponse('You already have another assessment at this time.', [], 409);
            }

            $assessment->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $doctor->id
            ]);

            try {
                $assessment->player->notifications()->create([
                    'type' => 'assessment',
                    'title' => 'Assessment Request Approved',
                    'body' => 'Your assessment request has been approved.',
                    'sender_id' => $doctor->id,
                    'related_assessment_id' => $assessment->id
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to notify player about assessment approval: ' . $e->getMessage());
            }

            return $this->successResponse([
                'id' => $assessment->id,
                'status' => 'approved'
            ], 'Assessment approved successfully');

        } catch (\Exception $e) {
            \Log::error('Assessment approval failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to approve assessment', [], 500);
        }
    }

    public function rescheduleAssessment(Request $request, AssessmentRequest $assessment): JsonResponse
    {
        try {
            // Validate incoming date and time
            $request->validate([
                'new_date' => 'required|date',
                'new_time' => 'required|date_format:H:i'
            ]);

            $doctorId = auth()->id();

            if ($doctorId !== $assessment->doctor_id) {
                return $this->errorResponse('You are not authorized to reschedule this assessment.', [], 403);
            }

            $newDateTime = Carbon::parse($request->new_date . ' ' . $request->new_time);

            // Check if doctor has another assessment at the same time
            $doctorConflict = AssessmentRequest::where('doctor_id', $doctorId)
                ->where('requested_at', $newDateTime)
                ->where('id', '!=', $assessment->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();

            if ($doctorConflict) {
                return $this->errorResponse(
                    'You already have an assessment scheduled at this time.',
                    [],
                    409
                );
            }

            // Check if player has another assessment at the same time
            $playerConflict = AssessmentRequest::where('player_id', $assessment->player_id)
                ->where('requested_at', $newDateTime)
                ->where('id', '!=', $assessment->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();

            if ($playerConflict) {
                return $this->errorResponse(
                    'This player already has an assessment scheduled at this time.',
                    [],
                    409
                );
            }

            // Update the assessment request
            $assessment->update([
                'requested_at' => $newDateTime,
                'status' => 'postponed'
            ]);

            // Notify the player
            $assessment->player->notifications()->create([
                'type' => 'assessment',
                'title' => 'Assessment Request Postponed',
                'body' => 'Your assessment request has been postponed to ' . $newDateTime->format('M d, Y H:i'),
                'sender_id' => $doctorId,
                'related_assessment_id' => $assessment->id
            ]);

            return $this->successResponse([
                'id' => $assessment->id,
                'status' => 'postponed',
                'new_time' => $newDateTime->toDateTimeString()
            ], 'Assessment rescheduled successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to reschedule assessment: ' . $e->getMessage());
            return $this->errorResponse('Failed to reschedule assessment', [], 500);
        }
    }





    public function approveClassification(User $player, Request $request)
    {
        try {
            // Direct authorization check
            if (auth()->user()->role !== 'doctor') {
                return $this->errorResponse('Only doctors can approve classifications', [], 403);
            }

            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
            }

            $request->validate([
                'approved' => 'required|boolean'
            ]);

            // Get the latest training program for this player
            $program = $player->trainingPrograms()
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$program) {
                return $this->errorResponse('No pending training program found for this player', [], 404);
            }

            if ($request->approved) {
                DB::beginTransaction();
                try {
                    // Update program status
                    $program->update([
                        'status' => 'approved',
                        'approved_at' => now()
                    ]);

                    // Create notification for the player
                    $player->notifications()->create([
                        'type' => 'training_program',
                        'title' => 'Training Program Approved',
                        'body' => 'Your training program and health classification have been approved by the doctor.',
                        'sender_id' => auth()->id(),
                        'related_program_id' => $program->id
                    ]);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                // If rejected, update program status
                $program->update([
                    'status' => 'rejected',
                    'approved_at' => now()
                ]);

                // Create notification for the player
                $player->notifications()->create([
                    'type' => 'training_program',
                    'title' => 'Training Program Rejected',
                    'body' => 'Your training program and health classification have been rejected by the doctor.',
                    'sender_id' => auth()->id(),
                    'related_program_id' => $program->id
                ]);
            }

            return $this->successResponse([
                'message' => $request->approved ? 'Training program and classification approved' : 'Training program and classification rejected',
                'player' => $player->fresh(),
                'program' => $program->fresh()
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to process approval: ' . $e->getMessage(), [], 500);
        }
    }

    public function approveAIProgram(TrainingProgram $program)
    {
        if (auth()->user()->role !== 'doctor') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only doctors can approve programs.',
                'data' => []
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Update program status
            $program->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);

            // Send notification to the player
            Notification::create([
                'type' => 'training_program',
                'title' => 'Training Program Approved',
                'body' => 'Your AI-generated training program has been approved by the doctor.',
                'sender_id' => auth()->id(),
                'related_program_id' => $program->id,
                'user_id' => $program->player_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Program approved successfully',
                'data' => [
                    'program' => $program->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve program: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
