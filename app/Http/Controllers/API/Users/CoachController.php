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

class CoachController extends BaseController
{
    protected $metricAnalysisService;

    public function __construct(MetricAnalysisService $metricAnalysisService)
    {
        $this->metricAnalysisService = $metricAnalysisService;
    }

    

 
    public function getDashboard()
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
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Error:', ['message' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch dashboard data', [], 500);
        }
    }

    public function getProfile()
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
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch profile data', [], 500);
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
        try {
            if ($player->role !== 'player') {
                return $this->errorResponse('Invalid player selected', [], 400);
            }

            $latestProgram = TrainingProgram::where('player_id', $player->id)
                ->latest()
                ->first();

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
    
            return $this->successResponse([
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'status' => $player->status
                ],
                'program' => [
                    'id' => $latestProgram->id,
                    'exercises' => $latestProgram->exercises,
                    'focus_area' => $latestProgram->focus_area,
                    'created_at' => $latestProgram->created_at
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch player program', [], 500);
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
}