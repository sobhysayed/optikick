<?php

namespace App\Services;

use App\Models\User;
use App\Models\PlayerMetric;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AIModelService
{
    private $apiUrl = 'https://fastapi-predictorg-production.up.railway.app/predict';

    public function classifyPlayer(PlayerMetric $metrics)
    {
        try {
            $response = Http::timeout(30)->post($this->apiUrl, [
                'fatigue_score' => $metrics->fatigue_score,
                'injury_risk' => $metrics->injury_risk,
                'readiness_score' => $metrics->readiness_score
            ]);

            if (!$response->successful()) {
                // Fallback to default training program if AI API fails
                return $this->getFallbackTrainingProgram($metrics);
            }

            $data = $response->json();

            $result = [
                'status' => $data['Predicted Status'] ?? null,
                'focus_area' => $data['Focus Area'] ?? null,
                'training_program' => $data['Training Program'] ?? null
            ];

            // Validate the response
            if (!$result['focus_area'] || !$result['training_program']) {
                return $this->getFallbackTrainingProgram($metrics);
            }

            return $result;
        } catch (\Exception $e) {
            // Fallback to default training program
            return $this->getFallbackTrainingProgram($metrics);
        }
    }

    /**
     * Get fallback training program when AI API is not available
     */
    private function getFallbackTrainingProgram(PlayerMetric $metrics)
    {
        // Determine focus area based on metrics
        $focusArea = 'General Fitness';
        if ($metrics->fatigue_score > 70) {
            $focusArea = 'Recovery and Rest';
        } elseif ($metrics->injury_risk > 70) {
            $focusArea = 'Injury Prevention';
        } elseif ($metrics->readiness_score < 30) {
            $focusArea = 'Low Intensity Training';
        }

        return [
            'status' => 'Optimal',
            'focus_area' => $focusArea,
            'training_program' => [
                'Warm-up: 10 minutes light cardio',
                'Main session: 30 minutes moderate intensity training',
                'Cool-down: 10 minutes stretching',
                'Focus on: ' . $focusArea
            ]
        ];
    }

    public function generateTrainingProgram(User $player, PlayerMetric $metrics, User $doctor)
    {
        try {
            DB::beginTransaction();

            // Generate AI prediction
            $aiPrediction = $this->classifyPlayer($metrics);

            // Create the training program
            $program = TrainingProgram::create([
                'player_id' => $player->id,
                'doctor_id' => $doctor->id,
                'exercises' => [
                    'focus_area' => $aiPrediction['focus_area'],
                    'program' => $aiPrediction['training_program']
                ],
                'focus_area' => $aiPrediction['focus_area'],
                'status' => 'pending',
                'ai_generated' => true
            ]);

            // Get the admin who generated the program
            $admin = auth()->user();

            // 1. Notify the doctor to review
            $doctor->notifications()->create([
                'user_id' => $doctor->id,
                'type' => 'training_program',
                'title' => 'Training Program Requires Review',
                'body' => "A new training program has been generated for player {$player->name}. Please review and approve.",
                'sender_id' => $admin->id,
                'related_program_id' => $program->id,
                'read_at' => null,
                'is_pinned' => false
            ]);

            // 2. Notify the coach
            $coach = $this->getPlayerCoach($player);
            if ($coach) {
                $coach->notifications()->create([
                    'user_id' => $coach->id,
                    'type' => 'training_program',
                    'title' => 'Training Program Generated',
                    'body' => "A training program has been set for player {$player->name} and is pending doctor approval.",
                    'sender_id' => $admin->id,
                    'related_program_id' => $program->id,
                    'read_at' => null,
                    'is_pinned' => false
                ]);
            }

            // 3. Notify the player
            $player->notifications()->create([
                'user_id' => $player->id,
                'type' => 'training_program',
                'title' => 'Training Program Generated',
                'body' => 'A new training program has been generated for you. It is currently pending doctor approval.',
                'sender_id' => $admin->id,
                'related_program_id' => $program->id,
                'read_at' => null,
                'is_pinned' => false
            ]);

            DB::commit();

            return $program;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to generate training program: ' . $e->getMessage());
        }
    }

    /**
     * Get the coach associated with a player
     */
    private function getPlayerCoach(User $player)
    {
        // For now, just get the first available coach
        // This can be enhanced later to find the specific coach for the player's team
        return User::where('role', 'coach')->first();
    }
}
