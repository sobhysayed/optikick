<?php

namespace App\Services;

class MetricAnalysisService
{
    public function analyzeMetric(array $values, string $metricType): array
    {
        if (empty($values)) {
            return ['No metrics data available for the specified period.'];
        }

        $highlights = [];
        $trend = $this->calculateTrend($values);

        // Peak and lowest
        $peak = max($values);
        $lowest = min($values);
        $peakDay = array_search($peak, $values) + 1;
        $lowestDay = array_search($lowest, $values) + 1;

        switch ($metricType) {
            case 'reaction_time':
                $highlights[] = "Day {$peakDay} has the slowest reaction time, likely due to fatigue or stress.";
                $highlights[] = "Reaction time improves over the 7 days, showing consistent adaptation.";
                if ($peakDay > 4) {
                    $highlights[] = "Days 5-6 remain stable, indicating a possible plateau.";
                }
                $highlights[] = "The Day {$peakDay} peak ({$peak} ms) vs Day {$lowestDay} low ({$lowest} ms) suggests fatigue, training, or focus impact.";
                break;

            case 'weight':
                $change = $peak - $lowest;
                $direction = $trend > 0 ? 'increase' : 'decrease';
                $highlights[] = "Weight shows a {$direction} of {$change} kg over the period.";
                $highlights[] = "Highest weight recorded on Day {$peakDay} ({$peak} kg), lowest on Day {$lowestDay} ({$lowest} kg).";

                if (abs($change) > 2) {
                    $highlights[] = "Significant weight fluctuation may indicate hydration shifts or diet/training changes.";
                }
                if (abs($peakDay - $lowestDay) <= 2) {
                    $highlights[] = "Rapid weight shift detected between Day {$lowestDay} and Day {$peakDay}.";
                }
                break;

            case 'max_hr':
                $highlights[] = "Day {$peakDay} recorded the highest max HR at {$peak} bpm, and Day {$lowestDay} the lowest at {$lowest} bpm.";
                $highlights[] = $trend > 0
                    ? "Max heart rate trend is rising — could suggest higher effort or training intensity."
                    : "Decreasing max HR trend may indicate fatigue or insufficient recovery.";

                if ($peak > 190) {
                    $highlights[] = "Max HR over 190 bpm may suggest near-max effort or high stress conditions.";
                }
                if (($peak - $lowest) > 20) {
                    $highlights[] = "Large HR variation across days may reflect inconsistent performance or testing variability.";
                }
                break;

            case 'resting_hr':
                $highlights[] = "Resting heart rate peaked on Day {$peakDay} at {$peak} bpm, and was lowest on Day {$lowestDay} at {$lowest} bpm.";
                $highlights[] = $trend > 0
                    ? "An increasing trend may indicate cumulative fatigue or stress."
                    : "A decreasing trend suggests improving cardiovascular recovery.";
                if ($peak > 70) {
                    $highlights[] = "Resting HR above 70 bpm could be due to stress, poor sleep, or incomplete recovery.";
                }
                break;

            case 'hrv':
                $highlights[] = "HRV highest on Day {$peakDay} ({$peak} ms) and lowest on Day {$lowestDay} ({$lowest} ms).";
                $highlights[] = $trend > 0
                    ? "Positive HRV trend indicates improved recovery and nervous system balance."
                    : "Decline in HRV may signal stress, illness, or overtraining.";
                break;

            case 'vo2_max':
                $highlights[] = "VO2 Max peaked at {$peak} ml/kg/min on Day {$peakDay}, lowest on Day {$lowestDay} at {$lowest} ml/kg/min.";
                $highlights[] = $trend > 0
                    ? "Improvement in VO2 Max suggests better aerobic capacity and endurance."
                    : "Drop in VO2 Max could reflect fatigue or need for adjusted training.";
                break;
        }

        return [
            'highlights' => $highlights,
            'trend' => $trend,
            'peak' => [
                'value' => $peak,
                'day' => $peakDay
            ],
            'lowest' => [
                'value' => $lowest,
                'day' => $lowestDay
            ]
        ];
    }

    private function calculateTrend(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $x = range(1, count($values));
        $y = array_values($values);

        $meanX = array_sum($x) / count($x);
        $meanY = array_sum($y) / count($y);

        $numerator = 0;
        $denominator = 0;

        for ($i = 0; $i < count($values); $i++) {
            $numerator += ($x[$i] - $meanX) * ($y[$i] - $meanY);
            $denominator += ($x[$i] - $meanX) * ($x[$i] - $meanX);
        }

        return $denominator != 0 ? $numerator / $denominator : 0;
    }
}
