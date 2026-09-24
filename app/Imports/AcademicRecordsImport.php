<?php

namespace App\Imports;

use App\Models\AcademicRecord;
use App\Models\User;
use App\Rules\SafeEmail;
use App\Services\PredictionEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AcademicRecordsImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public function __construct(private readonly User $actor) {}

    public function collection(Collection $rows): void
    {
        if ($rows->count() > 5000) {
            throw ValidationException::withMessages([
                'file' => 'Imports are limited to 5,000 data rows per file.',
            ]);
        }

        $seen = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = collect($row->toArray())
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all();

            if (collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:120'],
                'student_id' => ['required', 'string', 'max:40'],
                'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150'],
                'subject' => ['required', 'string', Rule::in(config('prediction.subjects'))],
                'section' => ['required', 'string', Rule::in(config('prediction.sections'))],
                'academic_year' => ['required', 'string', Rule::in(config('prediction.academic_years'))],
                ...array_fill_keys(PredictionEngine::INDICATORS, ['nullable', 'numeric', 'min:0', 'max:100']),
            ]);

            if ($validator->fails()) {
                $messages = collect($validator->errors()->messages())
                    ->flatMap(fn (array $errors) => array_map(
                        fn (string $message) => "Row {$rowNumber}: {$message}",
                        $errors,
                    ))
                    ->values()
                    ->all();

                throw ValidationException::withMessages(['file' => $messages]);
            }

            $this->ensureWithinScope($data['subject'], $data['section'], $rowNumber);

            $email = $data['email'];
            $studentId = $data['student_id'];
            $student = User::where('email', $email)->first();
            $studentById = User::where('student_id', $studentId)->first();

            if ($student && $studentById && $student->id !== $studentById->id) {
                throw $this->rowError($rowNumber, 'email and student_id belong to different accounts.');
            }

            $student ??= $studentById;

            if ($student && ! $student->isRole(User::ROLE_STUDENT)) {
                throw $this->rowError($rowNumber, "{$email} is not a student account.");
            }

            if ($student && ($student->email !== $email || $student->student_id !== $studentId)) {
                throw $this->rowError($rowNumber, 'The email and student_id must match the existing student account.');
            }

            if ($student && $student->section !== null && $student->section !== $data['section']) {
                throw $this->rowError($rowNumber, 'The row section does not match the student profile section.');
            }

            $student ??= User::create([
                'name' => $data['name'],
                'email' => $email,
                'student_id' => $studentId,
                'role' => User::ROLE_STUDENT,
                'section' => $data['section'],
                'academic_year' => $data['academic_year'],
                // Roster imports do not grant instructor-created accounts a known password.
                // An administrator can set the initial password from Manage students.
                'password' => Str::random(48),
            ]);

            $recordKey = implode('|', [$student->id, $data['subject'], $data['section'], $data['academic_year']]);

            if (isset($seen[$recordKey]) || AcademicRecord::where([
                'student_id' => $student->id,
                'subject' => $data['subject'],
                'section' => $data['section'],
                'academic_year' => $data['academic_year'],
            ])->exists()) {
                throw $this->rowError($rowNumber, 'This student already has a record for that subject, section and academic year.');
            }

            $seen[$recordKey] = true;
            $indicators = collect($data)->only(PredictionEngine::INDICATORS)
                ->map(fn ($value) => $value === '' ? null : $value)
                ->all();

            $record = new AcademicRecord([
                ...$indicators,
                'student_id' => $student->id,
                'instructor_id' => $this->actor->isRole(User::ROLE_INSTRUCTOR) ? $this->actor->id : null,
                'subject' => $data['subject'],
                'section' => $data['section'],
                'academic_year' => $data['academic_year'],
            ]);
            $record->recalculate()->save();

            $this->created++;
        }
    }

    /** Instructors may only import rows for their assigned subject and section. */
    private function ensureWithinScope(string $subject, string $section, int $rowNumber): void
    {
        if (! $this->actor->isRole(User::ROLE_INSTRUCTOR)) {
            return;
        }

        if (! $this->actor->canManageScope($subject, $section)) {
            throw $this->rowError(
                $rowNumber,
                "Subject \"{$subject}\" and section \"{$section}\" are outside your assigned scope.",
            );
        }
    }

    private function rowError(int $rowNumber, string $message): ValidationException
    {
        return ValidationException::withMessages([
            'file' => ["Row {$rowNumber}: {$message}"],
        ]);
    }
}
