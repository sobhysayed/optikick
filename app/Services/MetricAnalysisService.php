<?php

namespace App\Services;

/**
 * Class MetricAnalysisService
 * Analyzes health or performance metrics and returns insights.
 */
class MetricAnalysisService
{
    /**
     * Analyze a set of metric values and return trend insights.
     *
     * @param array $values Daily metric values (numeric).
     * @param string $metricType Metric type (e.g., 'reaction_time', 'weight').
     * @return array Highlights and statistics.
     */
    public function analyzeMetric(array $values, string $metricType): array
    {
        if (empty($values)) {
            return ['highlights' => ['No metrics data available for the specified period.']];
        }

        $highlights = [];
        $trend = $this->calculateTrend($values);
        [$peak, $peakDay] = $this->getPeak($values);
        [$lowest, $lowestDay] = $this->getLowest($values);

        $change = $peak - $lowest;
        $trendDirection = $trend > 0 ? 'increase' : ($trend < 0 ? 'decrease' : 'stable');

        switch ($metricType) {
            case 'reaction_time':
                $highlights[] = "Day {$peakDay} had the slowest reaction time ({$peak} ms), possibly due to fatigue or stress.";
                $highlights[] = "Day {$lowestDay} had the fastest reaction time ({$lowest} ms).";
                $highlights[] = $trend > 0
                    ? "Reaction time worsens over time, indicating rising fatigue or external stressors."
                    : "Reaction time improves over the period, showing good adaptation.";
                if (abs($peakDay - $lowestDay) <= 2) {
                    $highlights[] = "Notable fluctuation occurred in a short window.";
                }
                break;

            case 'weight':
                $highlights[] = "Weight {$trendDirection} of " . abs($change) . " kg observed.";
                $highlights[] = "Heaviest on Day {$peakDay} ({$peak} kg), lightest on Day {$lowestDay} ({$lowest} kg).";
                if (abs($change) > 2) {
                    $highlights[] = "Significant fluctuation may reflect changes in hydration, diet, or training.";
                }
                break;

            case 'max_hr':
                $highlights[] = "Highest Max HR on Day {$peakDay} ({$peak} bpm), lowest on Day {$lowestDay} ({$lowest} bpm).";
                $highlights[] = $trend > 0
                    ? "Increasing trend may indicate higher training intensity or stress."
                    : "Decreasing trend suggests potential fatigue or better recovery.";
                if ($peak > 190) {
                    $highlights[] = "HR above 190 bpm may signal intense effort or stress.";
                }
                break;

            case 'resting_hr':
                $highlights[] = "Resting HR peaked at {$peak} bpm (Day {$peakDay}), lowest was {$lowest} bpm (Day {$lowestDay}).";
                $highlights[] = $trend > 0
                    ? "Rising resting HR may indicate poor recovery or stress."
                    : "Decreasing trend points to improved recovery.";
                if ($peak > 70) {
                    $highlights[] = "Elevated resting HR might be due to overtraining, illness, or poor sleep.";
                }
                break;

            case 'hrv':
                $highlights[] = "HRV ranged from {$lowest} ms (Day {$lowestDay}) to {$peak} ms (Day {$peakDay}).";
                $highlights[] = $trend > 0
                    ? "Increasing HRV trend suggests improved recovery and nervous system balance."
                    : "Declining HRV could indicate fatigue, stress, or overtraining.";
                break;

            case 'vo2_max':
                $highlights[] = "VO2 Max was highest on Day {$peakDay} ({$peak} ml/kg/min), lowest on Day {$lowestDay} ({$lowest} ml/kg/min).";
                $highlights[] = $trend > 0
                    ? "VO2 Max improvement implies better aerobic capacity."
                    : "Decline may indicate fatigue or inadequate training stimulus.";
                break;

            default:
                $highlights[] = "Metric type '{$metricType}' is not recognized.";
                break;
        }

        return [
            'highlights' => $highlights,
            'trend' => round($trend, 4),
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

    /**
     * Calculate linear trend (slope) from the given values.
     *
     * @param array $values
     * @return float
     */
    private function calculateTrend(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $x = range(1, $n);
        $y = array_values($values);

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $numerator = 0;
        $denominator = 0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $numerator += $dx * $dy;
            $denominator += $dx * $dx;
        }

        return $denominator !== 0 ? $numerator / $denominator : 0;
    }

    /**
     * Get the peak value and its day.
     *
     * @param array $values
     * @return array [value, day]
     */
    private function getPeak(array $values): array
    {
        $peakValue = max($values);
        $day = array_search($peakValue, $values) + 1;
        return [$peakValue, $day];
    }

    /**
     * Get the lowest value and its day.
     *
     * @param array $values
     * @return array [value, day]
     */
    private function getLowest(array $values): array
    {
        $lowestValue = min($values);
        $day = array_search($lowestValue, $values) + 1;
        return [$lowestValue, $day];
    }
}
