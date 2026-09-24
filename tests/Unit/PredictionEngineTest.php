<?php

namespace Tests\Unit;

use App\Services\PredictionEngine;
use Tests\TestCase;

class PredictionEngineTest extends TestCase
{
    public function test_prediction_status_thresholds_are_stable(): void
    {
        $engine = app(PredictionEngine::class);

        $this->assertSame('Passed', $engine->evaluate(array_fill_keys(PredictionEngine::INDICATORS, 75))[1]);
        $this->assertSame('At Risk', $engine->evaluate(array_fill_keys(PredictionEngine::INDICATORS, 60))[1]);
        $this->assertSame('Failed', $engine->evaluate(array_fill_keys(PredictionEngine::INDICATORS, 59))[1]);
        $this->assertSame('No Prediction yet', $engine->evaluate([])[1]);
        $this->assertSame('No Prediction yet', $engine->evaluate(array_fill_keys(PredictionEngine::INDICATORS, null))[1]);
    }

    public function test_weighted_engine_combines_only_available_indicators(): void
    {
        $engine = app(PredictionEngine::class);

        // (60 * 0.15 + 90 * 0.20) / (0.15 + 0.20) = 27 / 0.35 = 77.14
        [$score, $status] = $engine->evaluate(['attendance' => 60, 'final_grade' => 90]);

        $this->assertEqualsWithDelta(77.14, $score, 0.01);
        $this->assertSame('Passed', $status);
    }

    public function test_missing_indicators_are_not_counted_as_zero(): void
    {
        $engine = app(PredictionEngine::class);

        [$score, $status] = $engine->evaluate(['quiz' => 50]);

        $this->assertSame(50.0, $score);
        $this->assertSame('Failed', $status);
    }

    public function test_numeric_strings_from_forms_are_accepted(): void
    {
        $engine = app(PredictionEngine::class);

        [$score, $status] = $engine->evaluate(array_fill_keys(PredictionEngine::INDICATORS, '88.5'));

        $this->assertSame(88.5, $score);
        $this->assertSame('Passed', $status);
    }

    public function test_status_for_helper_matches_configured_bands(): void
    {
        $engine = app(PredictionEngine::class);

        $this->assertSame('Passed', $engine->statusFor(100));
        $this->assertSame('Passed', $engine->statusFor(75));
        $this->assertSame('At Risk', $engine->statusFor(74.99));
        $this->assertSame('At Risk', $engine->statusFor(60));
        $this->assertSame('Failed', $engine->statusFor(59.99));
        $this->assertSame('Failed', $engine->statusFor(0));
    }
}