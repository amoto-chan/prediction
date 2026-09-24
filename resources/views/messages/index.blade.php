@extends('layouts.app')
@section('content')
<div class="mb-8">
    <p class="eyebrow">Communications</p>
    <h1 class="page-title">Messages</h1>
    <p class="mt-2 text-slate-500">{{ $unreadCount }} unread · Instructors and admins can notify every At Risk / Failed student in one action.</p>
</div>

<div class="grid gap-6 lg:grid-cols-[1.15fr_.85fr]"
     x-data="{
         picked: @js(array_values(array_filter([$prefillRecipient]))),
         cands: @js($candidates),
         selectAtRisk() { this.picked = this.cands.filter(c => c.status === 'At Risk' || c.status === 'Failed').map(c => c.id); },
         init() { @js($autoAtRisk) && this.selectAtRisk(); },
     }">
    <div class="space-y-6">
        <section class="panel">
            <div class="mb-4 flex items-center justify-between">
                <div><h2 class="section-title">Inbox</h2><p class="text-sm text-slate-500">Messages sent to you</p></div>
                <span class="badge badge-admin">{{ $unreadCount }} unread</span>
            </div>
            <div class="space-y-3">
                @forelse ($received as $message)
                    <article class="rounded-2xl border {{ $message->read_at ? 'border-slate-200 dark:border-slate-800' : 'border-violet-300 bg-violet-50/60 dark:border-violet-700 dark:bg-violet-950/30' }} p-4 transition hover:-translate-y-0.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <b class="text-sm">{{ $message->subject }}</b>
                            <time class="text-xs text-slate-400">{{ $message->created_at->format('M d, Y g:i A') }}</time>
                        </div>
                        <p class="mt-1 text-xs font-semibold text-violet-600 dark:text-violet-400">From {{ $message->sender?->name ?? 'System' }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $message->body }}</p>
                        @unless ($message->read_at)
                            <form method="POST" action="{{ route('messages.read', $message) }}" class="mt-3">
                                @csrf @method('PATCH')
                                <button class="text-xs font-bold text-violet-600 transition hover:underline">Mark as read</button>
                            </form>
                        @endunless
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">Your inbox is clear.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $received->links() }}</div>
        </section>

        <section class="panel">
            <h2 class="section-title">Sent</h2>
            <div class="mt-4 space-y-3">
                @forelse ($sent as $message)
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                        <div class="flex items-center justify-between gap-2">
                            <b class="text-sm">{{ $message->subject }}</b>
                            <time class="text-xs text-slate-400">{{ $message->created_at->format('M d, Y') }}</time>
                        </div>
                        <p class="mt-1 text-xs font-semibold text-slate-500">To {{ $message->recipient?->name ?? 'Unknown' }}</p>
                        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($message->body, 160) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">You have not sent any messages yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Compose (staff only) --}}
    <section class="panel h-fit" id="compose">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="section-title">Send message</h2><p class="text-sm text-slate-500">Reach students inside your scope</p></div>
            @if ($candidates->isNotEmpty())
                <button type="button" @click="selectAtRisk()"
                        class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-700 transition hover:bg-amber-500 hover:text-white dark:bg-amber-950 dark:text-amber-300">
                    Select all At Risk / Failed
                </button>
            @endif
        </div>

        @if ($candidates->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700">
                Student messaging is available to administrators and instructors with an assigned scope.
            </p>
        @else
            <form method="POST" action="{{ route('messages.bulk') }}" class="space-y-4">
                @csrf
                <div>
                    <p class="field !mb-2">Recipients — <span class="font-black text-violet-600" x-text="picked.length + ' selected'"></span></p>
                    <div class="max-h-64 space-y-1.5 overflow-y-auto rounded-2xl border border-slate-200 p-3 dark:border-slate-700">
                        @foreach ($candidates as $candidate)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2 transition hover:bg-violet-50 dark:hover:bg-violet-950/40">
                                <input type="checkbox" name="recipient_ids[]" value="{{ $candidate['id'] }}" x-model="picked"
                                       class="h-4 w-4 rounded border-violet-300 text-violet-600 focus:ring-violet-500">
                                <span class="min-w-0 flex-1">
                                    <b class="block truncate text-sm">{{ $candidate['name'] }}</b>
                                    <small class="text-xs text-slate-500">{{ $candidate['student_id'] }} · {{ $candidate['section'] }}</small>
                                </span>
                                <span class="badge badge-{{ \Illuminate\Support\Str::slug($candidate['status']) }} shrink-0">{{ $candidate['status'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <label class="field">Subject
                    <input name="subject" required value="{{ old('subject') }}" placeholder="Academic consultation notice">
                </label>
                <label class="field">Message
                    <textarea name="body" rows="5" required placeholder="Write a supportive, actionable message…">{{ old('body') }}</textarea>
                </label>
                <button class="btn-primary w-full justify-center" :disabled="picked.length === 0"
                        :class="picked.length === 0 ? 'opacity-50' : ''">
                    Send to selected students →
                </button>
            </form>
        @endif
    </section>
</div>
@endsection