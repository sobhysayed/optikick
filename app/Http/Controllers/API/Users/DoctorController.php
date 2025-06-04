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

class DoctorController extends Controller
{
    use AuthorizesRequests;
    
    private $metricAnalysisService;

    public function __construct(MetricAnalysisService $metricAnalysisService)
    {
        $this->metricAnalysisService = $metricAnalysisService;
    }

    // Add these protected methods for responses
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

    public function respondToAssessment(Request $request, AssessmentRequest $assessment)
    {
        try {
            $this->authorize('respond-to-assessment', $assessment);

            $request->validate([
                'action' => 'required|in:approve,postpone',
                'new_date' => 'required_if:action,postpone|date',
                'new_time' => 'required_if:action,postpone|date_format:H:i'
            ]);

            if ($request->action === 'approve') {
                $assessment->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id()
                ]);

                $assessment->player->notifications()->create([
                    'type' => 'assessment_approved',
                    'title' => 'Assessment Request Approved',
                    'body' => 'Your assessment request has been approved.',
                    'related_assessment_id' => $assessment->id
                ]);
            } else {
                $newDateTime = Carbon::parse($request->new_date . ' ' . $request->new_time);
                
                $assessment->update([
                    'requested_at' => $newDateTime,
                    'status' => 'postponed'
                ]);

                $assessment->player->notifications()->create([
                    'type' => 'assessment_postponed',
                    'title' => 'Assessment Request Postponed',
                    'body' => 'Your assessment request has been postponed to ' . $newDateTime->format('M d, Y H:i'),
                    'related_assessment_id' => $assessment->id
                ]);
            }

            return $this->successResponse($assessment->load(['player.profile']));
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to respond to assessment', [], 500);
        }
    }

    
 
    public function approveClassification(User $player, Request $request)
    {
        try {
            $this->authorize('approve-classification', $player);

            $request->validate([
                'status' => 'required|string',
                'approved' => 'required|boolean'
            ]);

            if ($request->approved) {
                $player->update(['status' => $request->status]);
                
                $player->notifications()->create([
                    'type' => 'classification_approved',
                    'title' => 'AI Classification Approved',
                    'body' => 'Your health classification has been approved by the doctor.'
                ]);
            }

            return $this->successResponse([
                'message' => $request->approved ? 'Classification approved' : 'Classification rejected',
                'player' => $player->fresh()
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve classification: ' . $e->getMessage(), [], 500);
        }
    }

    public function approveAIProgram(TrainingProgram $program, Request $request)
    {
        try {
            $this->authorize('approve-program', $program);
                
            $request->validate([
                'approved' => 'required|boolean'
            ]);

            if ($request->approved) {
                $program->update([
                    'status' => 'approved',
                    'approved_at' => now()
                ]);

                $program->player->notifications()->create([
                    'type' => 'program_approved',
                    'title' => 'Training Program Approved',
                    'body' => 'Your AI-generated training program has been approved by the doctor.',
                    'related_program_id' => $program->id
                ]);
            } else {
                $program->delete();
            }

            return $this->successResponse([
                'message' => $request->approved ? 'Program approved successfully' : 'Program rejected',
                'program' => $request->approved ? $program->fresh() : null
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve program: ' . $e->getMessage(), [], 500);
        }
    }
}