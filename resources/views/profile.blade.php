@extends('layouts.app')
@section('content')
@php($u = auth()->user())

<div class="mb-8">
    <p class="eyebrow">Account</p>
    <h1 class="page-title">Profile settings</h1>
    <p class="mt-2 text-slate-500">Keep your contact details and sign-in credentials current. Changes are written to the system log.</p>
</div>

<div class="grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
    <section class="panel">
        <div class="flex items-center gap-4">
            <span class="avatar !h-16 !w-16 !text-xl" style="background: {{ $u->avatar_color }}22; color: {{ $u->avatar_color }}">{{ $u->initials() }}</span>
            <div class="min-w-0">
                <h2 class="truncate text-lg font-black">{{ $u->name }}</h2>
                <p class="truncate text-sm text-slate-500">{{ $u->email }}</p>
                <span class="badge badge-{{ $u->role }} mt-2 capitalize">{{ $u->role }}</span>
            </div>
        </div>

        <dl class="mt-6 space-y-3 text-sm">
            @if ($u->student_id)
                <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60"><dt class="font-semibold text-slate-500">Student ID</dt><dd class="font-bold">{{ $u->student_id }}</dd></div>
            @endif
            @if ($u->section)
                <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60"><dt class="font-semibold text-slate-500">Section</dt><dd class="font-bold">{{ $u->section }}</dd></div>
            @endif
            @if ($u->academic_year)
                <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60"><dt class="font-semibold text-slate-500">Academic year</dt><dd class="font-bold">{{ $u->academic_year }}</dd></div>
            @endif
            @if ($u->isRole('instructor'))
                <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                    <dt class="font-semibold text-slate-500">Assigned subjects</dt>
                    <dd class="mt-1.5 flex flex-wrap gap-1.5">@foreach ($u->assignedSubjects() as $s)<span class="badge badge-instructor">{{ $s }}</span>@endforeach @if ($u->assignedSubjects() === [])<span class="text-slate-400">None yet</span>@endif</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                    <dt class="font-semibold text-slate-500">Assigned sections</dt>
                    <dd class="mt-1.5 flex flex-wrap gap-1.5">@foreach ($u->assignedSections() as $s)<span class="badge badge-admin">{{ $s }}</span>@endforeach @if ($u->assignedSections() === [])<span class="text-slate-400">None yet</span>@endif</dd>
                </div>
            @endif
            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60"><dt class="font-semibold text-slate-500">Member since</dt><dd class="font-bold">{{ $u->created_at?->format('M d, Y') }}</dd></div>
        </dl>
    </section>

    <section class="panel">
        <h2 class="section-title">Edit profile</h2>
        <p class="mt-1 text-sm text-slate-500">Leave the password fields blank to keep your current password.</p>
        <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PATCH')
            <label class="field">Full name
                <input name="name" value="{{ old('name', $u->name) }}" required>
            </label>
            <label class="field">Email address
                <input type="email" name="email" value="{{ old('email', $u->email) }}" required>
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="field">Current password <span class="font-normal text-slate-400">(required to change password)</span>
                    <input type="password" name="current_password" autocomplete="current-password">
                </label>
                <label class="field">New password
                    <input type="password" name="password" autocomplete="new-password" placeholder="••••••••">
                </label>
                <label class="field">Confirm new password
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="••••••••">
                </label>
            </div>
            <button class="btn-primary">Save profile <span>→</span></button>
        </form>
    </section>
</div>
@endsection