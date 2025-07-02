<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use App\Models\AssessmentRequest;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin');
    }

    public function getDashboard()
    {
        // Get total counts
        $totalUsers = User::count();
        $totalTeams = Team::count();
        $totalAssessments = AssessmentRequest::count();
        $totalPrograms = TrainingProgram::count();

        // Get user counts by role
        $usersByRole = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        // Get recent activities
        $recentAssessments = AssessmentRequest::with(['player.profile', 'doctor.profile'])
            ->latest()
            ->take(5)
            ->get();

        $recentPrograms = TrainingProgram::with(['player.profile', 'doctor.profile'])
            ->latest()
            ->take(5)
            ->get();

        // Get system health metrics
        $systemHealth = [
            'active_users' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
            'pending_assessments' => AssessmentRequest::where('status', 'pending')->count(),
            'active_programs' => TrainingProgram::where('status', 'active')->count(),
            'total_teams' => $totalTeams
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => [
                    'total_users' => $totalUsers,
                    'total_teams' => $totalTeams,
                    'total_assessments' => $totalAssessments,
                    'total_programs' => $totalPrograms
                ],
                'users_by_role' => $usersByRole,
                'recent_activities' => [
                    'assessments' => $recentAssessments,
                    'programs' => $recentPrograms
                ],
                'system_health' => $systemHealth
            ]
        ]);
    }

    public function getUsers(Request $request)
    {
        $query = User::with('profile');

        // Apply filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhereHas('profile', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function getTeams(Request $request)
    {
        $query = Team::with(['coach.profile', 'players.profile']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
        }

        $teams = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $teams
        ]);
    }

    public function getSystemStats()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
                'by_role' => User::select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->get()
            ],
            'assessments' => [
                'total' => AssessmentRequest::count(),
                'pending' => AssessmentRequest::where('status', 'pending')->count(),
                'completed' => AssessmentRequest::where('status', 'completed')->count()
            ],
            'programs' => [
                'total' => TrainingProgram::count(),
                'active' => TrainingProgram::where('status', 'active')->count(),
                'pending' => TrainingProgram::where('status', 'pending')->count()
            ],
            'teams' => [
                'total' => Team::count(),
                'active' => Team::where('status', 'active')->count()
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
} 