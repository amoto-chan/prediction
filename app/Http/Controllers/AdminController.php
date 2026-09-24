<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Models\SystemLog;
use App\Models\User;
use App\Rules\SafeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /* ------------------------------------------------------------------ */
    /* Add users (students & instructors)                                  */
    /* ------------------------------------------------------------------ */

    public function users()
    {
        return view('admin.users', [
            'users' => User::latest()->get(),
            'subjects' => config('prediction.subjects'),
            'sections' => config('prediction.sections'),
            'academicYears' => config('prediction.academic_years'),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['student', 'instructor'])],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8', 'max:4096'],
            'student_id' => ['nullable', 'string', 'max:40', 'unique:users,student_id'],
            'section' => ['nullable', 'string', 'max:40'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => [Rule::in(config('prediction.subjects'))],
            'sections' => ['nullable', 'array'],
            'sections.*' => [Rule::in(config('prediction.sections'))],
        ]);

        // Role-conditional required fields.
        if ($data['role'] === 'student') {
            Validator::make($data, [
                'student_id' => 'required|string|max:40|unique:users,student_id',
                'section' => ['required', Rule::in(config('prediction.sections'))],
                'academic_year' => ['required', Rule::in(config('prediction.academic_years'))],
            ])->validate();
        } else {
            Validator::make($data, [
                'subjects' => 'required|array|min:1',
                'sections' => 'required|array|min:1',
            ])->validate();
        }

        DB::transaction(function () use ($data, $request): void {
            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'student_id' => $data['role'] === 'student' ? $data['student_id'] : null,
                'section' => $data['role'] === 'student' ? $data['section'] : null,
                'academic_year' => $data['role'] === 'student' ? $data['academic_year'] : null,
                'subjects' => $data['role'] === 'instructor' ? array_values($data['subjects'] ?? []) : null,
                'sections' => $data['role'] === 'instructor' ? array_values($data['sections'] ?? []) : null,
                'avatar_color' => $data['role'] === 'instructor' ? '#0ea5e9' : '#7c3aed',
            ]);

            SystemLog::create([
                'user_id' => $request->user()->id,
                'action' => 'Created user',
                'description' => sprintf('Created %s account for %s (%s).', $data['role'], $data['name'], $data['email']),
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('success', ucfirst($data['role'])." account for {$data['name']} created. They can now sign in.");
    }

    public function destroyUser(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');
        abort_unless(in_array($user->role, ['student', 'instructor'], true), 422, 'Only student and instructor accounts can be removed here.');

        $email = $user->email;
        $user->delete();

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Deleted user',
            'description' => "Deleted account {$email}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Account deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Manage students                                                     */
    /* ------------------------------------------------------------------ */

    public function students()
    {
        return view('admin.students', [
            'students' => User::role('student')
                ->with('records:id,student_id,score,prediction')
                ->orderBy('name')
                ->get(),
            'sections' => config('prediction.sections'),
            'academicYears' => config('prediction.academic_years'),
            'subjects' => config('prediction.subjects'),
        ]);
    }

    public function showStudent(User $student)
    {
        abort_unless($student->isRole('student'), 403);

        return view('admin.students-show', [
            'student' => $student->load(['records' => fn ($query) => $query->latest(), 'records.instructor']),
            'sections' => config('prediction.sections'),
            'academicYears' => config('prediction.academic_years'),
        ]);
    }

    public function updateStudent(Request $request, User $student)
    {
        abort_unless($student->isRole('student'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150', 'unique:users,email,'.$student->id],
            'student_id' => ['required', 'string', 'max:40', 'unique:users,student_id,'.$student->id],
            'section' => ['required', Rule::in(config('prediction.sections'))],
            'academic_year' => ['required', Rule::in(config('prediction.academic_years'))],
            'password' => ['nullable', 'confirmed', 'min:8', 'max:4096'],
        ]);

        $student->fill(collect($data)->only(['name', 'email', 'student_id', 'section', 'academic_year'])->all());

        if (! empty($data['password'])) {
            $student->password = $data['password'];
        }

        $student->save();

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Updated student',
            'description' => "Updated profile of {$student->name} ({$student->student_id}).",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Student updated.');
    }

    public function assignSubject(Request $request, User $student)
    {
        abort_unless($student->isRole('student'), 403);

        $data = $request->validate([
            'subject' => ['required', Rule::in(config('prediction.subjects'))],
        ]);

        $year = $student->academic_year ?: config('prediction.academic_years.2');

        $exists = AcademicRecord::where('student_id', $student->id)
            ->where('subject', $data['subject'])
            ->where('academic_year', $year)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'subject' => "{$student->name} is already enrolled in {$data['subject']} for {$year}.",
            ]);
        }

        // Prefer an instructor assigned to both the section and the subject.
        $instructors = User::role('instructor')->get();
        $instructor = $instructors->first(fn (User $i) => $i->canManageScope($data['subject'], $student->section));

        $record = new AcademicRecord([
            'student_id' => $student->id,
            'instructor_id' => $instructor?->id,
            'subject' => $data['subject'],
            'section' => $student->section,
            'academic_year' => $year,
        ]);
        $record->recalculate()->save();

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Assigned subject',
            'description' => "Assigned {$data['subject']} to {$student->name} (prediction pending).",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "{$data['subject']} assigned to {$student->name}. Prediction is pending until grades are entered.");
    }

    public function destroyStudent(Request $request, User $student)
    {
        abort_unless($student->isRole('student'), 403);
        abort_if($student->id === $request->user()->id, 422, 'You cannot delete your own account.');

        $name = $student->name;
        $student->delete();

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Deleted student',
            'description' => "Deleted student {$name}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Student deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Manage instructors                                                  */
    /* ------------------------------------------------------------------ */

    public function instructors()
    {
        return view('admin.instructors', [
            'instructors' => User::role('instructor')
                ->withCount('taughtRecords as records_handled')
                ->orderBy('name')
                ->get(),
            'subjects' => config('prediction.subjects'),
            'sections' => config('prediction.sections'),
        ]);
    }

    public function updateInstructor(Request $request, User $instructor)
    {
        abort_unless($instructor->isRole('instructor'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150', 'unique:users,email,'.$instructor->id],
            'subjects' => 'required|array|min:1',
            'subjects.*' => [Rule::in(config('prediction.subjects'))],
            'sections' => 'required|array|min:1',
            'sections.*' => [Rule::in(config('prediction.sections'))],
            'password' => ['nullable', 'confirmed', 'min:8', 'max:4096'],
        ]);

        $instructor->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'subjects' => array_values($data['subjects']),
            'sections' => array_values($data['sections']),
        ]);

        if (! empty($data['password'])) {
            $instructor->password = $data['password'];
        }

        $instructor->save();

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Updated instructor',
            'description' => sprintf(
                'Updated %s — subjects: %s; sections: %s.',
                $instructor->name,
                implode(', ', $instructor->assignedSubjects()),
                implode(', ', $instructor->assignedSections())
            ),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Instructor updated.');
    }

    public function destroyInstructor(Request $request, User $instructor)
    {
        abort_unless($instructor->isRole('instructor'), 403);
        abort_if($instructor->id === $request->user()->id, 422, 'You cannot delete your own account.');

        $name = $instructor->name;
        $instructor->delete();

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'Deleted instructor',
            'description' => "Deleted instructor {$name}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Instructor deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* System logs                                                         */
    /* ------------------------------------------------------------------ */

    public function logs(Request $request)
    {
        $query = SystemLog::with('user');

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('action', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('ip_address', 'like', $term);
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        return view('admin.logs', [
            'logs' => $query->latest()->paginate(15)->withQueryString(),
            'actions' => SystemLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'actionFilter' => $request->string('action')->toString(),
        ]);
    }
}