<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use App\Models\TrainingProgram;
use App\Models\PlayerMetric;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\MetricAnalysisService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

class CoachController extends BaseController
{
    protected $metricAnalysisService;

    public function __construct(MetricAnalysisService $metricAnalysisService)
    {
        $this->metricAnalysisService = $metricAnalysisService;
    }


    public function getDashboard(): JsonResponse
    {
        try {
            $coach = auth()->user();

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
            ], 'Dashboard data fetched successfully');

        } catch (\Exception $e) {
            \Log::error('Dashboard Error:', ['message' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch dashboard data', [], 500);
        }
    }

    public function getProfile(): JsonResponse
    {
        try {
            $coach = auth()->user()->load('profile');
            $birthDate = Carbon::parse($coach->profile->date_of_birth);

            return $this->successResponse([
                'first_name' => $coach->profile->first_name,
                'last_name' => $coach->profile->last_name,
                'date_of_birth' => $birthDate->format('d F Y') . ' (' . $birthDate->age . ')',
                'sex' => $coach->profile->sex,
                'position' => $coach->profile->position,
                'blood_type' => $coach->profile->blood_type,
                'email' => $coach->email
            ], 'Coach profile fetched successfully');
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
        try {
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
            }

            $latestProgram = TrainingProgram::where('player_id', $player->id)
                ->latest()
                ->first();

            // No training program at all
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

            // Use latest if it's approved
            if ($latestProgram->status === 'approved') {
                $program = $latestProgram;
            } else {
                // Fallback to last approved one
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
}
