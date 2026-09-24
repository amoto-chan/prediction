@extends('layouts.app')
@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="eyebrow">Administration</p>
        <h1 class="page-title">Manage instructors</h1>
        <p class="mt-2 text-slate-500">{{ $instructors->count() }} instructor(s) · assign the subjects and sections each instructor handles.</p>
    </div>
    <a href="{{ route('admin.users') }}" class="btn-primary">+ Add instructor</a>
</div>

<section class="panel overflow-hidden" x-data="{ open: null }">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr><th>Instructor</th><th>Records handled</th><th>Assigned sections</th><th>Assigned subjects</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($instructors as $instructor)
                    <tr class="table-row">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="avatar" style="background: {{ $instructor->avatar_color }}22; color: {{ $instructor->avatar_color }}">{{ $instructor->initials() }}</span>
                                <span><b>{{ $instructor->name }}</b><small>{{ $instructor->email }}</small></span>
                            </div>
                        </td>
                        <td><b>{{ $instructor->records_handled }}</b></td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($instructor->assignedSections() as $section)<span class="badge badge-admin">{{ $section }}</span>@empty<span class="text-slate-400">—</span>@endforelse
                            </div>
                        </td>
                        <td class="max-w-md">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($instructor->assignedSubjects() as $subject)<span class="badge badge-instructor">{{ $subject }}</span>@empty<span class="text-slate-400">—</span>@endforelse
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="open = open === {{ $instructor->id }} ? null : {{ $instructor->id }}"
                                        class="rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white dark:bg-violet-950 dark:text-violet-300"
                                        :class="open === {{ $instructor->id }} ? 'ring-2 ring-violet-400' : ''">Edit</button>
                                <form method="POST" action="{{ route('admin.instructors.destroy', $instructor) }}" onsubmit="return confirm('Delete {{ $instructor->name }}? Their records remain but lose the instructor link.')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-600 hover:text-white dark:bg-rose-950">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="open === {{ $instructor->id }}" x-cloak x-transition class="!bg-violet-50/60 dark:!bg-violet-950/30">
                        <td colspan="5" class="!border-violet-200 dark:!border-violet-900">
                            <form method="POST" action="{{ route('admin.instructors.update', $instructor) }}" class="space-y-4 p-2">
                                @csrf @method('PATCH')
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="field">Full name<input name="name" value="{{ old('name', $instructor->name) }}" required></label>
                                    <label class="field">Email<input type="email" name="email" value="{{ old('email', $instructor->email) }}" required></label>
                                </div>
                                <div>
                                    <p class="field !mb-2">Subjects handled <span class="font-normal text-slate-400">(select at least one)</span></p>
                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($subjects as $subject)
                                            <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-white px-3 py-2.5 text-xs font-semibold shadow-sm transition hover:bg-violet-100 dark:bg-slate-900 dark:hover:bg-violet-950">
                                                <input type="checkbox" name="subjects[]" value="{{ $subject }}"
                                                       @checked(in_array($subject, old('subjects', $instructor->assignedSubjects()))) class="rounded border-violet-300 text-violet-600 focus:ring-violet-500">
                                                {{ $subject }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <p class="field !mb-2">Sections handled <span class="font-normal text-slate-400">(scope for records &amp; messaging)</span></p>
                                    <div class="grid gap-2 sm:grid-cols-4">
                                        @foreach ($sections as $section)
                                            <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-white px-3 py-2.5 text-xs font-semibold shadow-sm transition hover:bg-violet-100 dark:bg-slate-900 dark:hover:bg-violet-950">
                                                <input type="checkbox" name="sections[]" value="{{ $section }}"
                                                       @checked(in_array($section, old('sections', $instructor->assignedSections()))) class="rounded border-violet-300 text-violet-600 focus:ring-violet-500">
                                                {{ $section }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="field">New password <span class="font-normal text-slate-400">(optional)</span><input type="password" name="password" placeholder="Leave blank to keep current"></label>
                                    <label class="field">Confirm password<input type="password" name="password_confirmation" placeholder="••••••••"></label>
                                </div>
                                <button class="btn-primary">Save instructor →</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-slate-500">No instructors yet — create one from “Add users”.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection