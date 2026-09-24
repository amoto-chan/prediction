<?php

namespace App\Models;

use App\Services\PredictionEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_STUDENT = 'student';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_INSTRUCTOR,
        self::ROLE_STUDENT,
    ];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'student_id',
        'section',
        'academic_year',
        'subjects',
        'sections',
        'avatar_color',
    ];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subjects' => 'array',
            'sections' => 'array',
        ];
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = Str::lower(trim($value));
    }

    /* ------------------------------------------------------------------ */
    /* Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function records(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'student_id');
    }

    public function taughtRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'instructor_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    /* ------------------------------------------------------------------ */
    /* Roles & scope                                                       */
    /* ------------------------------------------------------------------ */

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    /** @return list<string> */
    public function assignedSubjects(): array
    {
        return is_array($this->subjects) ? array_values($this->subjects) : [];
    }

    /** @return list<string> */
    public function assignedSections(): array
    {
        return is_array($this->sections) ? array_values($this->sections) : [];
    }

    /**
     * Academic records this user is allowed to see.
     *
     * Instructor scope is the intersection of assigned subjects and sections;
     * an absent assignment is an empty scope, never an unrestricted scope.
     */
    public function scopedRecords(): Builder
    {
        if ($this->isRole(self::ROLE_ADMIN)) {
            return AcademicRecord::query();
        }

        if (! $this->isRole(self::ROLE_INSTRUCTOR)) {
            return AcademicRecord::where('student_id', $this->id);
        }

        $subjects = $this->assignedSubjects();
        $sections = $this->assignedSections();

        if ($subjects === [] || $sections === []) {
            return AcademicRecord::query()->whereRaw('1 = 0');
        }

        return AcademicRecord::query()
            ->whereIn('subject', $subjects)
            ->whereIn('section', $sections);
    }

    /** Whether this user may edit / delete the given academic record. */
    public function managesRecord(AcademicRecord $record): bool
    {
        if ($this->isRole(self::ROLE_ADMIN)) {
            return true;
        }

        return $this->isRole(self::ROLE_INSTRUCTOR)
            && in_array($record->subject, $this->assignedSubjects(), true)
            && in_array($record->section, $this->assignedSections(), true);
    }

    /** Whether a staff user may create a record for this subject and section. */
    public function canManageScope(string $subject, string $section): bool
    {
        if ($this->isRole(self::ROLE_ADMIN)) {
            return true;
        }

        return $this->isRole(self::ROLE_INSTRUCTOR)
            && in_array($subject, $this->assignedSubjects(), true)
            && in_array($section, $this->assignedSections(), true);
    }

    /** Whether this user may message the given student. */
    public function canMessageStudent(User $student): bool
    {
        if ($this->isRole(self::ROLE_ADMIN)) {
            return $student->isRole(self::ROLE_STUDENT);
        }

        if (! $this->isRole(self::ROLE_INSTRUCTOR) || ! $student->isRole(self::ROLE_STUDENT)) {
            return false;
        }

        return in_array($student->section, $this->assignedSections(), true)
            || $this->scopedRecords()->where('student_id', $student->id)->exists();
    }

    /* ------------------------------------------------------------------ */
    /* Presentation helpers                                                */
    /* ------------------------------------------------------------------ */

    /** Aggregated academic performance (Passed / At Risk / Failed / No Prediction yet). */
    public function academicPerformance(): string
    {
        if (! $this->isRole(self::ROLE_STUDENT)) {
            return '—';
        }

        $scores = $this->relationLoaded('records')
            ? $this->records->pluck('score')
            : $this->records()->pluck('score');

        $scores = $scores->filter(fn ($score) => $score !== null);

        if ($scores->isEmpty()) {
            return PredictionEngine::NO_PREDICTION;
        }

        return app(PredictionEngine::class)->statusFor((float) $scores->avg());
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: ['?'];

        return strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }
}
