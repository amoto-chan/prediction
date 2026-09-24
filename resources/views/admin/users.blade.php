@extends('layouts.app')
@section('content')
<div class="mb-8">
    <p class="eyebrow">Administration</p>
    <h1 class="page-title">Add users</h1>
    <p class="mt-2 text-slate-500">Create student and instructor accounts — users can only sign in once you create their account here.</p>
</div>

<div class="grid gap-6 xl:grid-cols-[.85fr_1.15fr]">
    <section class="panel h-fit" x-data="{ tab: 'student' }">
        <div class="mb-5 flex rounded-2xl bg-slate-100 p-1 dark:bg-slate-800">
            <button type="button" @click="tab = 'student'" class="flex-1 rounded-xl py-2.5 text-sm font-bold transition" :class="tab === 'student' ? 'bg-white text-violet-700 shadow dark:bg-slate-900 dark:text-violet-300' : 'text-slate-500'">Student</button>
            <button type="button" @click="tab = 'instructor'" class="flex-1 rounded-xl py-2.5 text-sm font-bold transition" :class="tab === 'instructor' ? 'bg-white text-violet-700 shadow dark:bg-slate-900 dark:text-violet-300' : 'text-slate-500'">Instructor</button>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="role" x-model="tab">

            <label class="field">Full name<input name="name" value="{{ old('name') }}" required></label>
            <label class="field">Email<input type="email" name="email" value="{{ old('email') }}" required></label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="field">Password<input type="password" name="password" required minlength="8"></label>
                <label class="field">Confirm password<input type="password" name="password_confirmation" required minlength="8"></label>
            </div>

            <div x-show="tab === 'student'" x-cloak class="grid gap-4 sm:grid-cols-2">
                <label class="field">Student ID<input name="student_id" value="{{ old('student_id') }}" placeholder="BSIT-2026-013"></label>
                <label class="field">Section
                    <select name="section" required>
                        <option value="">Select section…</option>
                        @foreach ($sections as $option)<option value="{{ $option }}" @selected(old('section') === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label class="field">Academic year
                    <select name="academic_year" required>
                        @foreach ($academicYears as $option)<option value="{{ $option }}" @selected(old('academic_year', $option) || (old('academic_year') === null && $option === config('prediction.academic_years.2')))>{{ $option }}</option>@endforeach
                    </select>
                </label>
            </div>

            <div x-show="tab === 'instructor'" x-cloak class="space-y-4">
                <div>
                    <p class="field !mb-2">Subjects handled <span class="font-normal text-slate-400">(at least one)</span></p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($subjects as $subject)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-slate-50 px-3 py-2.5 text-xs font-semibold transition hover:bg-violet-100 dark:bg-slate-800 dark:hover:bg-violet-950">
                                <input type="checkbox" name="subjects[]" value="{{ $subject }}" @checked(in_array($subject, old('subjects', []))) class="rounded border-violet-300 text-violet-600 focus:ring-violet-500">
                                {{ $subject }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="field !mb-2">Sections handled <span class="font-normal text-slate-400">(at least one)</span></p>
                    <div class="grid gap-2 sm:grid-cols-4">
                        @foreach ($sections as $section)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-slate-50 px-3 py-2.5 text-xs font-semibold transition hover:bg-violet-100 dark:bg-slate-800 dark:hover:bg-violet-950">
                                <input type="checkbox" name="sections[]" value="{{ $section }}" @checked(in_array($section, old('sections', []))) class="rounded border-violet-300 text-violet-600 focus:ring-violet-500">
                                {{ $section }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <button class="btn-primary w-full justify-center">Create <span x-text="tab"></span> account →</button>
            <p class="text-center text-xs text-slate-400">New accounts can sign in immediately with the password you set.</p>
        </form>
    </section>

    <section class="panel overflow-hidden">
        <div class="mb-5 flex items-center justify-between">
            <div><h2 class="section-title">User directory</h2><p class="text-sm text-slate-500">{{ $users->count() }} account(s)</p></div>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role</th><th>Details</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @forelse ($users as $listed)
                        <tr class="table-row">
                            <td><b>{{ $listed->name }}</b><small>{{ $listed->email }}</small></td>
                            <td><span class="badge badge-{{ $listed->role }}">{{ ucfirst($listed->role) }}</span></td>
                            <td class="text-slate-500">
                                @if ($listed->isRole('student'))
                                    {{ $listed->student_id ?? 'No ID' }} · {{ $listed->section ?? 'No section' }} · {{ $listed->academic_year ?? '—' }}
                                @elseif ($listed->isRole('instructor'))
                                    {{ count($listed->assignedSections()) }} section(s) · {{ count($listed->assignedSubjects()) }} subject(s)
                                @else
                                    Full access
                                @endif
                            </td>
                            <td class="text-xs text-slate-400">{{ $listed->created_at?->format('M d, Y') }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    @if ($listed->isRole('admin'))
                                        <span class="text-xs font-semibold text-slate-400">You</span>
                                    @else
                                        <a href="{{ $listed->isRole('student') ? route('admin.students.show', $listed) : route('admin.instructors') }}"
                                           class="rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white dark:bg-violet-950 dark:text-violet-300">Manage</a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $listed) }}" onsubmit="return confirm('Delete this account?')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-600 hover:text-white dark:bg-rose-950">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-slate-500">No accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection