<?php

namespace App\Services;

use App\Models\User;
use App\Models\PlayerMetric;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\Http;

class AIModelService
{
    private $apiUrl = 'https://fastapi-predictorg-production.up.railway.app/predict';

    public function classifyPlayer(PlayerMetric $metrics)
    {
        try {
            $response = Http::post($this->apiUrl, [
                'fatigue_score' => $metrics->fatigue_score,
                'injury_risk' => $metrics->injury_risk,
                'readiness_score' => $metrics->readiness_score
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get AI prediction: ' . $response->body());
            }

            $data = $response->json();
            return [
                'status' => $data['Predicted Status'] ?? null,
                'focus_area' => $data['Focus Area'] ?? null,
                'training_program' => $data['Training Program'] ?? null
            ];
        } catch (\Exception $e) {
            throw new \Exception('AI classification error: ' . $e->getMessage());
        }
    }

    public function generateTrainingProgram(User $player, PlayerMetric $metrics, User $doctor)
    {
        try {
            $aiPrediction = $this->classifyPlayer($metrics);

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

            // Send notification to the doctor
            $doctor->notifications()->create([
                'type' => 'training_program',
                'title' => 'AI Program Generated',
                'body' => 'A new AI-generated training program has been set for player ' . $player->name . '. Please review and approve.',
                'related_program_id' => $program->id,
                'sender_id' => auth()->id() // or the admin's ID if available
            ]);

            return $program;
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate training program: ' . $e->getMessage());
        }
    }

    public function approveAIPrediction(User $player, TrainingProgram $program)
    {
        try {
            $player->update(['status' => $program->exercises['predicted_status']]);
            $program->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to approve AI prediction: ' . $e->getMessage());
        }
    }
}