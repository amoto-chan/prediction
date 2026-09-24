@extends('layouts.app')
@section('content')
@php
    $enrolled = $student->records->pluck('subject')->all();
    $available = array_values(array_diff(config('prediction.subjects'), $enrolled));
@endphp

<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.students') }}" class="icon-btn" title="Back">←</a>
        <div>
            <p class="eyebrow">Manage students</p>
            <h1 class="page-title">{{ $student->name }}</h1>
            <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <span class="badge badge-student">{{ $student->student_id ?? 'No ID' }}</span>
                <span class="badge badge-admin">{{ $student->section ?? 'No section' }}</span>
                <span>{{ $student->academic_year ?? '—' }}</span>
                <span class="badge badge-{{ \Illuminate\Support\Str::slug($student->academicPerformance()) }}">{{ $student->academicPerformance() }}</span>
            </p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Delete {{ $student->name }} and all their records?')">
        @csrf @method('DELETE')
        <button class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-600 transition hover:bg-rose-50 dark:border-rose-900 dark:hover:bg-rose-950/40">Delete student</button>
    </form>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    {{-- Edit profile --}}
    <section class="panel">
        <h2 class="section-title">Edit student details</h2>
        <p class="mt-1 text-sm text-slate-500">Changes are recorded in the system log.</p>
        <form method="POST" action="{{ route('admin.students.update', $student) }}" class="mt-5 space-y-4">
            @csrf @method('PATCH')
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="field">Full name<input name="name" value="{{ old('name', $student->name) }}" required></label>
                <label class="field">Email<input type="email" name="email" value="{{ old('email', $student->email) }}" required></label>
                <label class="field">Student ID<input name="student_id" value="{{ old('student_id', $student->student_id) }}" required></label>
                <label class="field">Section
                    <select name="section" required>
                        @foreach ($sections as $option)<option value="{{ $option }}" @selected(old('section', $student->section) === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label class="field">Academic year
                    <select name="academic_year" required>
                        @foreach ($academicYears as $option)<option value="{{ $option }}" @selected(old('academic_year', $student->academic_year) === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label class="field">Set/reset password <span class="font-normal text-slate-400">(optional)</span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current">
                </label>
                <label class="field">Confirm password
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="••••••••">
                </label>
            </div>
            <button class="btn-primary">Save changes →</button>
        </form>
    </section>

    {{-- Assign subject --}}
    <section class="panel">
        <h2 class="section-title">Assign subject</h2>
        <p class="mt-1 text-sm text-slate-500">Assigned subjects start with “No Prediction yet” until grades are entered by the instructor.</p>

        @if ($available)
            <form method="POST" action="{{ route('admin.students.subjects', $student) }}" class="mt-5 flex flex-wrap items-end gap-3">
                @csrf
                <label class="field min-w-64 flex-1">Subject
                    <select name="subject" required>
                        @foreach ($available as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                    </select>
                </label>
                <button class="btn-primary !py-2.5">Assign</button>
            </form>
        @else
            <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Already enrolled in every catalogued subject. 🎉</p>
        @endif

        <div class="mt-6">
            <p class="field !mb-2">Enrolled subjects</p>
            <div class="space-y-2">
                @forelse ($student->records as $record)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                        <span class="min-w-0"><b class="block truncate text-sm">{{ $record->subject }}</b><small class="text-xs text-slate-500">{{ $record->academic_year }} · {{ $record->instructor?->name ?? 'Unassigned instructor' }}</small></span>
                        <span class="shrink-0 text-right">
                            <b class="block text-sm">{{ $record->score !== null ? number_format($record->score, 1).'%' : '—' }}</b>
                            <span class="badge badge-{{ \Illuminate\Support\Str::slug($record->prediction) }}">{{ $record->prediction }}</span>
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No subjects assigned yet.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection