<?php

namespace Database\Seeders;

use App\Models\PlayerMetric;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;

class PlayerMetricsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/player_metrics.csv');

        if (!file_exists($path)) {
            $this->command->error("CSV file not found at: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0); // First row is the header

        foreach ($csv->getRecords() as $record) {
            PlayerMetric::create([
                'player_id'         => $record['player_id'],
                'resting_hr'        => $record['resting_hr'],
                'max_hr'            => $record['max_hr'],
                'hrv'               => $record['hrv'],
                'vo2_max'           => $record['vo2_max'],
                'weight'            => $record['weight'],
                'reaction_time'     => $record['reaction_time'],
                'match_consistency' => $record['match_consistency'],
                'minutes_played'    => $record['minutes_played'],
                'training_hours'    => $record['training_hours'],
                'injury_frequency'  => $record['injury_frequency'],
                'recovery_time'     => $record['recovery_time'],
                'fatigue_score'     => $record['fatigue_score'],
                'injury_risk'       => $record['injury_risk'],
                'readiness_score'   => $record['readiness_score'],
                'recorded_at'       => Carbon::parse($record['recorded_at']),
                'created_at'        => Carbon::parse($record['created_at']),
                'updated_at'        => Carbon::parse($record['updated_at']),
            ]);
        }

        $this->command->info('Player metrics seeded successfully!');
    }
}
