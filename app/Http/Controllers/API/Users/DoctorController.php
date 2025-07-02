<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\TrainingProgram;
use App\Models\AssessmentRequest;
use App\Models\PlayerMetric;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\MetricAnalysisService;
use App\Services\AIModelService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;

class DoctorController extends Controller
{
    use AuthorizesRequests;

    private $metricAnalysisService;

    public function __construct(MetricAnalysisService $metricAnalysisService)
    {
        $this->metricAnalysisService = $metricAnalysisService;
    }

    protected function successResponse($data, $message = 'Operation successful', $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message, $data = [], $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function getDashboard()
    {
        try {
            $doctor = auth()->user();

            // Get players and their statuses directly from users table
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
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Error:', ['message' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch dashboard data', [], 500);
        }
    }

    public function getProfile()
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
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch profile data', [], 500);
        }
    }

    public function getPlayerMetrics(User $player)
    {
        try {
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
            }

            $metrics = $player->metrics()
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

            return $this->successResponse([
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'status' => $player->status
                ],
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch player metrics', [], 500);
        }
    }

    public function getPlayerMetricDetail(Request $request, User $player, string $metricType)
    {
        try {
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
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

            // Get analysis from metric service
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
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch metric detail', [], 500);
        }
    }

    public function getTeamOverview()
    {
        $coach = auth()->user();
        $players = User::where('role', 'player')
            ->with('profile')
            ->select('id', 'status', 'name')
            ->get()
            ->map(function($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'position' => $player->profile ? $player->profile->position : null,
                    'status' => $player->status
                ];
            });

        return response()->json($players);
    }

        public function getPlayerProgram(User $player)
    {
        // Only doctors can access this
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


    public function getAssessmentRequests()
    {
        try {
            $requests = auth()->user()->assignedAssessments()
                ->with(['player.profile'])
                ->latest()
                ->get();

            return $this->successResponse($requests);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch assessment requests', [], 500);
        }
    }

        public function approveAssessment(AssessmentRequest $assessment)
    {
        try {

            $doctor = auth()->user();

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

            return $this->successResponse(
                $assessment->load(['player.profile']),
                'Assessment approved successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Assessment approval failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to approve assessment', [], 500);
        }
    }


    public function rescheduleAssessment(Request $request, AssessmentRequest $assessment)
    {
        try {

            $request->validate([
                'new_date' => 'required|date',
                'new_time' => 'required|date_format:H:i'
            ]);

            $newDateTime = Carbon::parse($request->new_date . ' ' . $request->new_time);

            $assessment->update([
                'requested_at' => $newDateTime,
                'status' => 'postponed'
            ]);

            $assessment->player->notifications()->create([
                'type' => 'assessment',
                'title' => 'Assessment Request Postponed',
                'body' => 'Your assessment request has been postponed to ' . $newDateTime->format('M d, Y H:i'),
                'sender_id' => auth()->id(),
                'related_assessment_id' => $assessment->id
            ]);

            return $this->successResponse(
                $assessment->load(['player.profile']),
                'Assessment rescheduled successfully'
            );

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
