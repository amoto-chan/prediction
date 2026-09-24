@extends('layouts.app')
@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="eyebrow">Administration</p>
        <h1 class="page-title">Manage students</h1>
        <p class="mt-2 text-slate-500">{{ $students->count() }} student account(s) · view performance, edit details, assign sections &amp; subjects.</p>
    </div>
    <a href="{{ route('admin.users') }}" class="btn-primary">+ Add student</a>
</div>

<section class="panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th><th>Student ID</th><th>Academic performance</th><th>Academic year</th><th>Section</th><th>Subjects</th><th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr class="table-row">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="avatar" style="background: {{ $student->avatar_color }}22; color: {{ $student->avatar_color }}">{{ $student->initials() }}</span>
                                <span><b>{{ $student->name }}</b><small>{{ $student->email }}</small></span>
                            </div>
                        </td>
                        <td class="font-mono text-xs">{{ $student->student_id ?? '—' }}</td>
                        <td><span class="badge badge-{{ \Illuminate\Support\Str::slug($student->academicPerformance()) }}">{{ $student->academicPerformance() }}</span></td>
                        <td>{{ $student->academic_year ?? '—' }}</td>
                        <td><span class="badge badge-admin">{{ $student->section ?? '—' }}</span></td>
                        <td>{{ $student->records->count() }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.students.show', $student) }}"
                                   class="rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white dark:bg-violet-950 dark:text-violet-300">View / Edit</a>
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Delete {{ $student->name }} and all their records?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-600 hover:text-white dark:bg-rose-950">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">No student accounts yet — create one from “Add users”.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection