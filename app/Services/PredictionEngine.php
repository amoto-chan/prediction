<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Weighted academic performance prediction engine.
 *
 * Missing indicators are excluded from the weighted average rather than being
 * treated as zero. This keeps partially graded records fair and produces a
 * useful prediction as soon as at least one valid indicator is available.
 */
class PredictionEngine
{
    public const PASSED = 'Passed';

    public const AT_RISK = 'At Risk';

    public const FAILED = 'Failed';

    public const NO_PREDICTION = 'No Prediction yet';

    public const STATUSES = [
        self::PASSED,
        self::AT_RISK,
        self::FAILED,
        self::NO_PREDICTION,
    ];

    public const INDICATORS = [
        'attendance',
        'quiz',
        'midterm_grade',
        'final_grade',
        'exam',
        'assignment',
        'project_output',
        'laboratory_activities',
    ];

    /**
     * @param  array<string, mixed>  $values
     * @return array{0: float|null, 1: string}
     */
    public function evaluate(array $values): array
    {
        /** @var array<string, float> $weights */
        $weights = config('prediction.weights', []);
        $earned = 0.0;
        $totalWeight = 0.0;

        foreach (self::INDICATORS as $indicator) {
            $value = $values[$indicator] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if (! is_numeric($value)) {
                throw new InvalidArgumentException("The {$indicator} indicator must be numeric.");
            }

            $numericValue = (float) $value;

            if (! is_finite($numericValue) || $numericValue < 0 || $numericValue > 100) {
                throw new InvalidArgumentException("The {$indicator} indicator must be between 0 and 100.");
            }

            $weight = (float) ($weights[$indicator] ?? 0);

            if ($weight <= 0) {
                $weight = 1.0;
            }

            $earned += $numericValue * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0) {
            return [null, self::NO_PREDICTION];
        }

        $score = round($earned / $totalWeight, 2);

        return [$score, $this->statusFor($score)];
    }

    public function statusFor(float $score): string
    {
        $passed = (float) config('prediction.thresholds.passed', 75);
        $atRisk = (float) config('prediction.thresholds.at_risk', 60);

        if ($score >= $passed) {
            return self::PASSED;
        }

        if ($score >= $atRisk) {
            return self::AT_RISK;
        }

        return self::FAILED;
    }
}

