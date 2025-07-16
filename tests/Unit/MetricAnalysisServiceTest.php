<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MetricAnalysisService;

class MetricAnalysisServiceTest extends TestCase
{
    protected MetricAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetricAnalysisService();
    }

    /** @test */
    public function it_analyzes_reaction_time_metrics()
    {
        $values = [200, 180, 220, 190, 210];
        $result = $this->service->analyzeMetric($values, 'reaction_time');

        $this->assertEquals(220, $result['peak']['value']);
        $this->assertEquals(3, $result['peak']['day']);
        $this->assertEquals(180, $result['lowest']['value']);
        $this->assertEquals(2, $result['lowest']['day']);

        $highlights = implode(' ', $result['highlights']);
        $this->assertStringContainsString('Day 3 had the slowest reaction time (220 ms)', $highlights);
        $this->assertStringContainsString('Day 2 had the fastest reaction time (180 ms)', $highlights);
    }

    /** @test */
    public function it_calculates_positive_trend()
    {
        $values = [10, 20, 30, 40, 50];
        $result = $this->service->analyzeMetric($values, 'reaction_time');

        $this->assertGreaterThan(0, $result['trend']);
        $highlights = implode(' ', $result['highlights']);
        $this->assertStringContainsString('Reaction time worsens over time', $highlights);
    }

    /** @test */
    public function it_handles_unknown_metric_type()
    {
        $values = [10, 20, 30];
        $result = $this->service->analyzeMetric($values, 'unknown_metric');

        $this->assertArrayHasKey('highlights', $result);
        $this->assertStringContainsString("Metric type 'unknown_metric' is not recognized", $result['highlights'][0]);
    }

    /** @test */
    public function it_analyzes_weight_metrics()
    {
        $values = [75.5, 75.2, 76.1, 75.8, 75.0];
        $result = $this->service->analyzeMetric($values, 'weight');

        $this->assertEquals(76.1, $result['peak']['value']);
        $this->assertEquals(3, $result['peak']['day']);
        $this->assertEquals(75.0, $result['lowest']['value']);
        $this->assertEquals(5, $result['lowest']['day']);

        $highlights = implode(' ', $result['highlights']);
        $this->assertStringContainsString('Heaviest on Day 3 (76.1 kg)', $highlights);
        $this->assertStringContainsString('lightest on Day 5 (75 kg)', $highlights);
    }

    /** @test */
    public function it_returns_no_data_message_for_empty_values()
    {
        $result = $this->service->analyzeMetric([], 'reaction_time');

        $this->assertEquals(['No metrics data available for the specified period.'], $result['highlights']);
    }
}
