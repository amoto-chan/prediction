<?php

namespace App\Http\Controllers;

use App\Imports\AcademicRecordsImport;
use App\Models\AcademicRecord;
use App\Models\SystemLog;
use App\Models\User;
use App\Rules\SafeEmail;
use App\Services\PredictionEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Maatwebsite\Excel\Facades\Excel;

class RecordController extends Controller
{
    /** CSV columns expected by the import template. */
    public static function csvHeaders(): array
    {
        return ['name', 'student_id', 'email', 'subject', 'section', 'academic_year', ...PredictionEngine::INDICATORS];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'student_id' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150'],
            'subject' => ['required', Rule::in(config('prediction.subjects'))],
            'section' => ['required', Rule::in(config('prediction.sections'))],
            'academic_year' => ['required', Rule::in(config('prediction.academic_years'))],
            ...array_fill_keys(PredictionEngine::INDICATORS, ['nullable', 'numeric', 'min:0', 'max:100']),
        ]);

        $actor = $request->user();
        $this->ensureWithinScope($actor, $data['subject'], $data['section']);

        $newStudent = false;
        $student = DB::transaction(function () use ($actor, $data, &$newStudent) {
            $student = User::where('email', $data['email'])->first();
            $studentById = User::where('student_id', $data['student_id'])->first();

            if ($student && $studentById && $student->id !== $studentById->id) {
                throw ValidationException::withMessages([
                    'student_id' => 'The email and student ID belong to different accounts.',
                ]);
            }

            $student ??= $studentById;

            if ($student && ! $student->isRole(User::ROLE_STUDENT)) {
                throw ValidationException::withMessages([
                    'email' => 'That email belongs to a non-student account.',
                ]);
            }

            if ($student && ($student->email !== $data['email'] || $student->student_id !== $data['student_id'])) {
                throw ValidationException::withMessages([
                    'student_id' => 'The email and student ID must match the existing student account.',
                ]);
            }

            if ($student && $student->section !== null && $student->section !== $data['section']) {
                throw ValidationException::withMessages([
                    'section' => 'The selected section does not match the student profile.',
                ]);
            }

            if ($student) {
                if ($student->section === null) {
                    $student->update([
                        'section' => $data['section'],
                        'academic_year' => $data['academic_year'],
                    ]);
                }

                return $student->fresh();
            }

            $newStudent = true;

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'student_id' => $data['student_id'],
                'role' => User::ROLE_STUDENT,
                'section' => $data['section'],
                'academic_year' => $data['academic_year'],
                // Staff-created roster accounts receive no known default password.
                'password' => Str::random(48),
            ]);
        });

        $record = DB::transaction(function () use ($actor, $data, $student, $request) {
            $this->assertNoDuplicate($student, $data['subject'], $data['section'], $data['academic_year']);

            $indicators = collect($data)->only(PredictionEngine::INDICATORS)
                ->map(fn ($value) => $value === '' ? null : $value)
                ->all();

            $record = new AcademicRecord([
                ...$indicators,
                'student_id' => $student->id,
                'instructor_id' => $actor->isRole(User::ROLE_INSTRUCTOR) ? $actor->id : null,
                'subject' => $data['subject'],
                'section' => $data['section'],
                'academic_year' => $data['academic_year'],
            ]);
            $record->recalculate()->save();

            SystemLog::create([
                'user_id' => $actor->id,
                'action' => 'Added record',
                'description' => "Added {$record->subject} performance for {$student->name} — {$record->prediction}.",
                'ip_address' => $request->ip(),
            ]);

            return $record;
        });

        $message = "Record saved. Prediction: {$record->prediction}.";
        if ($newStudent) {
            $message .= ' An administrator must set the new student\'s login password.';
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, AcademicRecord $record)
    {
        $actor = $request->user();
        abort_unless($actor->managesRecord($record), 403);

        $data = $request->validate([
            'subject' => ['sometimes', 'required', Rule::in(config('prediction.subjects'))],
            'section' => ['sometimes', 'required', Rule::in(config('prediction.sections'))],
            'academic_year' => ['sometimes', 'required', Rule::in(config('prediction.academic_years'))],
            ...array_fill_keys(PredictionEngine::INDICATORS, ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100']),
        ]);

        $subject = $data['subject'] ?? $record->subject;
        $section = $data['section'] ?? $record->section;
        $academicYear = $data['academic_year'] ?? $record->academic_year;

        $this->ensureWithinScope($actor, $subject, $section);
        $this->assertNoDuplicate($record->student, $subject, $section, $academicYear, $record->id);

        $record->fill([
            ...collect($data)->only(PredictionEngine::INDICATORS)->all(),
            'subject' => $subject,
            'section' => $section,
            'academic_year' => $academicYear,
        ])->recalculate()->save();

        SystemLog::create([
            'user_id' => $actor->id,
            'action' => 'Updated record',
            'description' => "Updated {$record->subject} for {$record->student?->name} — {$record->prediction}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Record updated. Prediction: {$record->prediction}.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:2048'],
        ]);

        $import = new AcademicRecordsImport($request->user());

        try {
            DB::transaction(fn () => Excel::import($import, $request->file('file')));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (LaravelExcelException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'The spreadsheet could not be read. Please download the template and try again.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'The spreadsheet could not be imported. Check the file format and try again.',
            ]);
        }

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Imported records',
            'description' => "Imported {$import->created} performance record(s) from a CSV/XLSX file.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Imported {$import->created} performance record(s).");
    }

    /** Downloadable CSV template with example rows. */
    public function template()
    {
        $rows = [
            static::csvHeaders(),
            ['Juan Dela Cruz', 'BSIT-2026-001', 'juan.delacruz@cpsu-hinigaran.edu.ph', config('prediction.subjects.0'), 'BSIT 3A', config('prediction.academic_years.2'), 92, 88, 90, 94, 91, 89, 96, 93],
            ['Maria Santos', 'BSIT-2026-002', 'maria.santos@cpsu-hinigaran.edu.ph', config('prediction.subjects.0'), 'BSIT 3A', config('prediction.academic_years.2'), 71, 65, 68, 70, 66, 72, '', ''],
        ];

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'wb');

            foreach ($rows as $row) {
                $line = implode(',', array_map(
                    fn ($value) => '"'.str_replace('"', '""', (string) $value).'"',
                    $row,
                ));
                fwrite($stream, $line."\n");
            }

            fclose($stream);
        }, 'cpsu-academic-records-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Request $request, AcademicRecord $record)
    {
        $actor = $request->user();
        abort_unless($actor->managesRecord($record), 403);

        $description = "Deleted {$record->subject} record for {$record->student?->name}.";
        $record->delete();

        SystemLog::create([
            'user_id' => $actor->id,
            'action' => 'Deleted record',
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Record deleted.');
    }

    /** Instructors may only manage records for their assigned subject and section. */
    private function ensureWithinScope(User $actor, string $subject, string $section): void
    {
        if ($actor->isRole(User::ROLE_ADMIN)) {
            return;
        }

        if (! $actor->isRole(User::ROLE_INSTRUCTOR)) {
            throw ValidationException::withMessages([
                'subject' => 'You are not authorized to manage academic records.',
            ]);
        }

        if (! in_array($section, $actor->assignedSections(), true)) {
            throw ValidationException::withMessages([
                'section' => 'That section is outside your assigned scope.',
            ]);
        }

        if (! in_array($subject, $actor->assignedSubjects(), true)) {
            throw ValidationException::withMessages([
                'subject' => 'That subject is outside your assigned scope.',
            ]);
        }
    }

    private function assertNoDuplicate(?User $student, string $subject, string $section, string $academicYear, ?int $exceptId = null): void
    {
        if (! $student) {
            return;
        }

        $query = AcademicRecord::where([
            'student_id' => $student->id,
            'subject' => $subject,
            'section' => $section,
            'academic_year' => $academicYear,
        ]);

        if ($exceptId) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'subject' => 'This student already has a record for that subject, section and academic year.',
            ]);
        }
    }
}
