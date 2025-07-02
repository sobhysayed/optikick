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

        // Find peak and lowest values
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
                $highlights[] = "The Day {$peakDay} peak ({$peak}+ ms) vs Day {$lowestDay} drop ({$lowest}- ms) suggests fatigue, training, or focus impact.";
                break;

            case 'weight':
                $weightChange = $peak - $lowest;
                $highlights[] = $trend > 0
                    ? "Weight shows an increasing trend of {$weightChange} kg over the period."
                    : "Weight shows a decreasing trend of {$weightChange} kg over the period.";

                if (abs($weightChange) > 2) {
                    $highlights[] = "Significant weight change detected. Consider reviewing nutrition and training load.";
                }

                if ($peakDay - $lowestDay < 3) {
                    $highlights[] = "Rapid weight fluctuation between Day {$lowestDay} and Day {$peakDay}. May indicate hydration changes.";
                }
                break;

            case 'max_hr':
                $highlights[] = $trend > 0
                    ? "Increasing max heart rate could suggest higher workout intensity or testing conditions."
                    : "Decreasing max heart rate might reflect fatigue, underperformance, or poor recovery.";

                if ($peak > 190) {
                    $highlights[] = "Peak heart rate over 190 bpm may indicate near-max effort or high stress.";
                }

                if (($peak - $lowest) > 20) {
                    $highlights[] = "Large fluctuations in max HR across days could indicate inconsistent performance or varying effort levels.";
                }

                $highlights[] = "Day {$peakDay} peak ({$peak} bpm) vs Day {$lowestDay} low ({$lowest} bpm) gives insight into intensity swings.";
                break;

            case 'resting_hr':
                $highlights[] = $trend > 0
                    ? "Increasing resting heart rate trend may indicate accumulated fatigue."
                    : "Decreasing resting heart rate suggests improving cardiovascular fitness.";
                if (max($values) > 70) {
                    $highlights[] = "Peak heart rate above 70 bpm might indicate stress or incomplete recovery.";
                }
                break;

            case 'hrv':
                $highlights[] = $trend > 0
                    ? "Increasing HRV trend indicates improving recovery and adaptation."
                    : "Decreasing HRV trend suggests potential stress or fatigue.";
                break;

            case 'vo2_max':
                $highlights[] = $trend > 0
                    ? "VO2 Max is showing improvement, indicating enhanced aerobic capacity."
                    : "Slight decrease in VO2 Max, might need to adjust training intensity.";
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
