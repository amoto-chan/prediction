<?php

namespace App\Models;

use App\Services\PredictionEngine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    /**
     * Score and prediction are intentionally not mass-assignable. They are
     * derived fields and are always recalculated by the model event below.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'instructor_id',
        'subject',
        'section',
        'academic_year',
        ...PredictionEngine::INDICATORS,
    ];

    protected function casts(): array
    {
        return [
            'attendance' => 'float',
            'quiz' => 'float',
            'midterm_grade' => 'float',
            'final_grade' => 'float',
            'exam' => 'float',
            'assignment' => 'float',
            'project_output' => 'float',
            'laboratory_activities' => 'float',
            'score' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            $record->recalculate();
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function recalculate(): self
    {
        [$this->score, $this->prediction] = app(PredictionEngine::class)
            ->evaluate($this->only(PredictionEngine::INDICATORS));

        return $this;
    }
}
