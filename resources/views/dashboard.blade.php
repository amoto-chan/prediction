
@extends('layouts.app')
@section('content')
@php
    $u = auth()->user();
    $roleCopy = [
        'admin' => 'Every student, every prediction, every action — audited in real time.',
        'instructor' => 'Your assigned subjects and sections — intervene early where it matters.',
        'student' => 'Track your indicators, prediction status and next steps.',
    ];
    $dotColors = ['emerald' => '#10b981', 'amber' => '#f59e0b', 'rose' => '#f43f5e', 'slate' => '#94a3b8'];
@endphp

{{-- Hero --}}
<section class="dashboard-hero hero-grid mb-8 animate-rise">
    <div class="relative z-10 flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.22em] text-violet-300">
                {{ now()->format('l, F j, Y') }} · {{ now()->format('g:i:s A') }}
            </p>
            <h1 class="brand-display mt-3 text-3xl font-bold sm:text-4xl">
                Good {{ now()->format('G') < 12 ? 'morning' : (now()->format('G') < 18 ? 'afternoon' : 'evening') }}, {{ \Illuminate\Support\Str::before($u->name, ' ') }}
            </h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">{{ $roleCopy[$u->role] }}</p>
        </div>
        <div class="flex flex-col items-start gap-3 sm:items-end">
            <span class="live-dot bg-violet-500/20 text-violet-200">Live · <span x-text="now"></span></span>
            <div class="flex flex-wrap gap-2">
                @if ($u->isRole('instructor'))
                    <a href="#record-form" class="btn-primary">+ Add performance record</a>
                    <a href="#import-csv" class="rounded-xl border border-white/25 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/10">Import CSV</a>
                @elseif ($u->isRole('admin'))
                    <a href="{{ route('admin.users') }}" class="btn-primary">+ Add user</a>
                    <a href="{{ route('admin.logs') }}" class="rounded-xl border border-white/25 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/10">System logs</a>
                @else
                    <a href="#chatbot" class="btn-primary">Ask the AI chatbot</a>
                    <a href="#records" class="rounded-xl border border-white/25 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/10">My progress</a>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Key metrics --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <div class="stat-card card-hover">
        <span class="stat-icon bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300">👥</span>
        <div><small>Students monitored</small><b id="metric-monitored">{{ $monitoredStudents }}</b></div>
    </div>
    <div class="stat-card card-hover">
        <span class="stat-icon bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">✓</span>
        <div><small>Passed</small><b id="metric-passed">{{ $counts['Passed'] }}</b></div>
    </div>
    <div class="stat-card card-hover">
        <span class="stat-icon bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">!</span>
        <div><small>At Risk</small><b id="metric-at-risk">{{ $counts['At Risk'] }}</b></div>
    </div>
    <div class="stat-card card-hover">
        <span class="stat-icon bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">✕</span>
        <div><small>Failed</small><b id="metric-failed">{{ $counts['Failed'] }}</b></div>
    </div>
    <div class="stat-card card-hover">
        <span class="stat-icon bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">◔</span>
        <div><small>No prediction yet</small><b id="metric-no-prediction">{{ $counts['No Prediction yet'] }}</b></div>
    </div>
</div>

{{-- Live analytics: doughnut · bar · line · pie --}}
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="panel">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="section-title">Prediction distribution</h2><p class="text-sm text-slate-500">Status across records in your scope</p></div>
            <span class="live-dot">Live</span>
        </div>
        <div class="grid items-center gap-4 sm:grid-cols-2">
            <div class="chart-box !h-52"><canvas id="doughnutChart"></canvas></div>
            <div class="space-y-3">
                @foreach (['Passed' => 'emerald', 'At Risk' => 'amber', 'Failed' => 'rose', 'No Prediction yet' => 'slate'] as $label => $color)
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800/60">
                        <span class="flex items-center gap-2 font-semibold"><i class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $dotColors[$color] }}"></i>{{ $label }}</span>
                        <b data-metric="{{ \Illuminate\Support\Str::slug($label) }}">{{ $counts[$label] }}</b>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="section-title">Indicator averages</h2><p class="text-sm text-slate-500">Mean score per performance indicator</p></div>
            <span class="live-dot">Live</span>
        </div>
        <div class="chart-box"><canvas id="barChart"></canvas></div>
    </section>

    <section class="panel">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="section-title">Record activity</h2><p class="text-sm text-slate-500">Records created over the last 8 weeks</p></div>
            <span class="live-dot">Live</span>
        </div>
        <div class="chart-box"><canvas id="lineChart"></canvas></div>
    </section>

    <section class="panel">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="section-title">Coverage by section</h2><p class="text-sm text-slate-500">Records distributed across sections</p></div>
            <span class="live-dot">Live</span>
        </div>
        <div class="chart-box"><canvas id="pieChart"></canvas></div>
    </section>
</div>

{{-- Academic records / my progress --}}
<section id="records" class="panel mt-6 overflow-hidden"
         x-data="{
             open: null,
             title: '',
             form: {},
             records: {},
             fill(id) { this.open = id; this.title = this.title || ''; this.form = { ...(this.records[id] || {}) }; },
             init() { this.records = JSON.parse(this.$refs.json.textContent); },
         }">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="section-title">{{ $u->isRole('student') ? 'My progress — enrolled subjects' : 'Academic records' }}</h2>
            <p class="text-sm text-slate-500">
                {{ $u->isRole('student') ? 'All eight indicators, your instructor and the live prediction for each subject.' : ($records->count().' record(s) in your scope — edit grades to recalculate predictions instantly.') }}
            </p>
        </div>
        <span class="live-dot">Auto-scored</span>
    </div>

    <div class="overflow-x-auto">
        @if ($u->isRole('student'))
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th><th>Instructor</th>
                        @foreach (config('prediction.indicator_labels') as $label)<th>{{ $label }}</th>@endforeach
                        <th>Score</th><th>Prediction</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="table-row">
                            <td><b>{{ $record->subject }}</b><small>{{ $record->section }} · {{ $record->academic_year }}</small></td>
                            <td>{{ $record->instructor?->name ?? '—' }}</td>
                            @foreach (config('prediction.indicator_labels') as $key => $label)
                                <td>{{ $record->{$key} !== null ? number_format($record->{$key}, 1).'%' : '—' }}</td>
                            @endforeach
                            <td><b>{{ $record->score !== null ? number_format($record->score, 1).'%' : '—' }}</b></td>
                            <td><span class="badge badge-{{ \Illuminate\Support\Str::slug($record->prediction) }}">{{ $record->prediction }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="py-10 text-center text-slate-500">No subjects assigned yet — your instructor or the administrator will add them.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="data-table">
                <thead>
                    <tr><th>Student</th><th>Subject</th><th>Section</th><th>Academic year</th><th>Score</th><th>Prediction</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="table-row">
                            <td><b>{{ $record->student?->name ?? '—' }}</b><small>{{ $record->student?->student_id }}</small></td>
                            <td>{{ $record->subject }}</td>
                            <td>{{ $record->section }}</td>
                            <td>{{ $record->academic_year }}</td>
                            <td><b>{{ $record->score !== null ? number_format($record->score, 1).'%' : '—' }}</b></td>
                            <td><span class="badge badge-{{ \Illuminate\Support\Str::slug($record->prediction) }}">{{ $record->prediction }}</span></td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="title = @js($record->student?->name.' · '.$record->subject); fill({{ $record->id }})"
                                            class="rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white dark:bg-violet-950 dark:text-violet-300">Edit</button>
                                    <form method="POST" action="{{ route('records.destroy', $record) }}"
                                          onsubmit="return confirm('Delete this record?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-600 hover:text-white dark:bg-rose-950">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-500">No academic records yet. Use the form below or import a CSV.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    @php
        $modalSubjects = $u->isRole('instructor') ? ($u->assignedSubjects() ?: config('prediction.subjects')) : config('prediction.subjects');
        $modalSections = $u->isRole('instructor') ? ($u->assignedSections() ?: config('prediction.sections')) : config('prediction.sections');
    @endphp

    {{-- Record payload for the edit modal --}}
    <script type="application/json" x-ref="json">{!! json_encode($records->mapWithKeys(fn ($r) => [
        $r->id => ['subject' => $r->subject, 'section' => $r->section, 'academic_year' => $r->academic_year, ...collect(config('prediction.indicator_labels'))->keys()->mapWithKeys(fn ($k) => [$k => $r->{$k}])],
    ])) !!}</script>

    {{-- Edit record modal --}}
    <div class="modal-overlay" x-show="open !== null" x-cloak x-transition.opacity @click.self="open = null"
         @keydown.escape.window="open = null"
         x-data="{ labels: @js(config('prediction.indicator_labels')), keys: @js(array_keys(config('prediction.indicator_labels'))) }">
        <div class="modal-panel modal-panel-lg">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black">Edit performance record</h3>
                    <p class="text-sm text-slate-500" x-text="title"></p>
                </div>
                <button type="button" @click="open = null" class="icon-btn" aria-label="Close">✕</button>
            </div>

            <form method="POST" :action="'{{ url('/records') }}/' + open" class="space-y-4">
                @csrf
                @method('PATCH')
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="field">Subject
                        <select x-model="form.subject">
                            @foreach ($modalSubjects as $subject)<option value="{{ $subject }}">{{ $subject }}</option>@endforeach
                        </select>
                    </label>
                    <label class="field">Section
                        <select x-model="form.section">
                            @foreach ($modalSections as $section)<option value="{{ $section }}">{{ $section }}</option>@endforeach
                        </select>
                    </label>
                    <label class="field">Academic year
                        <select x-model="form.academic_year">
                            @foreach (config('prediction.academic_years') as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach
                        </select>
                    </label>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <template x-for="key in keys" :key="key">
                        <label class="field">
                            <span x-text="labels[key]"></span>
                            <input type="number" min="0" max="100" step="0.01" :name="key" x-model="form[key]" placeholder="—">
                        </label>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-violet-50 px-4 py-3 text-xs font-semibold text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                    <span>The weighted prediction is recalculated the moment you save. Leave a field blank to clear it.</span>
                    <button class="btn-primary !py-2.5">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</section>

{{-- Supporting panels --}}
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    @if ($u->isRole('instructor'))
        <section id="record-form" class="panel scroll-mt-24">
            <div class="mb-4">
                <h2 class="section-title">Add performance record</h2>
                <p class="text-sm text-slate-500">Create a student (or match an existing email) and record indicators — the prediction is calculated instantly.</p>
            </div>
            @php
                $formSections = $u->assignedSections() ?: config('prediction.sections');
                $formSubjects = $u->assignedSubjects() ?: config('prediction.subjects');
            @endphp
            <form method="POST" action="{{ route('records.store') }}" class="grid gap-3 sm:grid-cols-3">
                @csrf
                <label class="field">Student full name<input name="name" value="{{ old('name') }}" required></label>
                <label class="field">Student ID<input name="student_id" value="{{ old('student_id') }}" required></label>
                <label class="field">Email<input type="email" name="email" value="{{ old('email') }}" required></label>
                <label class="field">Subject
                    <select name="subject" required>
                        @foreach ($formSubjects as $option)<option value="{{ $option }}" @selected(old('subject') === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label class="field">Section
                    <select name="section" required>
                        @foreach ($formSections as $option)<option value="{{ $option }}" @selected(old('section') === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label class="field">Academic year
                    <select name="academic_year" required>
                        @foreach (config('prediction.academic_years') as $option)<option value="{{ $option }}" @selected(old('academic_year', config('prediction.academic_years.2')) === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                @foreach (config('prediction.indicator_labels') as $key => $label)
                    <label class="field">{{ $label }} (%)
                        <input type="number" name="{{ $key }}" min="0" max="100" step="0.01" value="{{ old($key) }}" placeholder="—">
                    </label>
                @endforeach
                <div class="sm:col-span-3">
                    <button class="btn-primary">Save &amp; calculate prediction <span>→</span></button>
                    <p class="mt-2 text-xs text-slate-400">Leave indicator fields blank for “No Prediction yet”. Only your assigned subjects / sections are accepted.</p>
                </div>
            </form>
        </section>

        <section id="import-csv" class="panel scroll-mt-24">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="section-title">Import CSV / spreadsheet</h2>
                    <p class="text-sm text-slate-500">Bulk-add performance records in one upload.</p>
                </div>
                <a href="{{ route('records.template') }}" class="rounded-lg bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white dark:bg-violet-950 dark:text-violet-300">Download template</a>
            </div>
            <form method="POST" action="{{ route('records.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <label class="field">File (.csv, .txt, .xlsx · max 2 MB)
                    <input type="file" name="file" accept=".csv,.txt,.xlsx" required>
                </label>
                <div class="rounded-xl bg-slate-50 p-4 text-xs leading-5 text-slate-500 dark:bg-slate-800/60">
                    <b class="text-slate-700 dark:text-slate-300">Required headers:</b><br>
                    name, student_id, email, subject, section, academic_year,
                    @foreach (array_keys(config('prediction.indicator_labels')) as $i => $key){{ $i ? ', ' : '' }}{{ $key }}@endforeach
                </div>
                <button class="btn-primary">Import records <span>↗</span></button>
            </form>
        </section>
    @endif

    {{-- Students needing intervention (admin + instructor) --}}
    @if (! $u->isRole('student') && $atRiskStudents->isNotEmpty())
        <section class="panel">
            <div class="mb-4 flex items-center justify-between">
                <div><h2 class="section-title">Students needing intervention</h2><p class="text-sm text-slate-500">Prediction is At Risk or Failed</p></div>
                <span class="badge badge-at-risk">{{ $atRiskStudents->count() }} student(s)</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($atRiskStudents as $item)
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:border-violet-300 dark:border-slate-800 dark:bg-slate-800/50">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="avatar" style="background: {{ $item['student']->avatar_color ?? '#7c3aed' }}22; color: {{ $item['student']->avatar_color ?? '#7c3aed' }}">{{ $item['student']?->initials() ?? '?' }}</span>
                            <span class="min-w-0">
                                <b class="block truncate text-sm">{{ $item['student']->name }}</b>
                                <small class="text-xs text-slate-500">{{ $item['student']->student_id }} · {{ $item['section'] }}</small>
                            </span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="badge badge-{{ \Illuminate\Support\Str::slug($item['status']) }}">{{ $item['status'] }}</span>
                            <a href="{{ route('messages.index', ['recipient' => $item['student']->id]) }}"
                               class="rounded-lg bg-violet-600 px-2.5 py-1.5 text-xs font-bold text-white transition hover:bg-violet-500" title="Send message">✉</a>
                            @if ($u->isRole('admin'))
                                <a href="{{ route('admin.students.show', $item['student']) }}"
                                   class="rounded-lg bg-white px-2.5 py-1.5 text-xs font-bold text-violet-700 shadow-sm transition hover:bg-violet-600 hover:text-white dark:bg-slate-900" title="Manage">⚙</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Recent messages --}}
    <section class="panel">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="section-title">Recent messages</h2><p class="text-sm text-slate-500">{{ $unreadCount }} unread in your inbox</p></div>
            <a href="{{ route('messages.index') }}" class="text-xs font-bold text-violet-600 transition hover:underline">Open inbox →</a>
        </div>
        <div class="space-y-3">
            @forelse ($inbox as $message)
                <div class="rounded-xl bg-slate-50 p-4 transition hover:bg-violet-50 dark:bg-slate-800/60 dark:hover:bg-violet-950/40">
                    <div class="flex items-center justify-between gap-2">
                        <b class="truncate text-sm">{{ $message->subject }}</b>
                        <time class="shrink-0 text-xs text-slate-400">{{ $message->created_at->diffForHumans() }}</time>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-violet-600 dark:text-violet-400">From {{ $message->sender?->name ?? 'System' }}</p>
                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($message->body, 140) }}</p>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700">Your inbox is clear.</p>
            @endforelse
        </div>
    </section>

    {{-- Admin: recent system activity --}}
    @if ($u->isRole('admin'))
        <section class="panel">
            <div class="mb-4 flex items-center justify-between">
                <div><h2 class="section-title">System activity</h2><p class="text-sm text-slate-500">Latest audited actions with IP addresses</p></div>
                <a href="{{ route('admin.logs') }}" class="text-xs font-bold text-violet-600 transition hover:underline">All logs →</a>
            </div>
            <div class="space-y-3">
                @forelse ($logs as $log)
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800">
                        <span class="flex min-w-0 items-start gap-3">
                            <span class="timeline-dot"></span>
                            <span class="min-w-0">
                                <b class="text-sm">{{ $log->user?->name ?? 'System' }}</b>
                                <span class="text-sm text-slate-500"> — {{ $log->action }}</span>
                                <small class="block truncate text-xs text-slate-400">{{ $log->description }} · {{ $log->ip_address }}</small>
                            </span>
                        </span>
                        <time class="shrink-0 text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</time>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No activity recorded yet.</p>
                @endforelse
            </div>
        </section>
    @endif

    @if ($u->isRole('student'))
        {{-- Assigned instructors --}}
        <section class="panel">
            <div class="mb-4">
                <h2 class="section-title">Assigned instructors</h2>
                <p class="text-sm text-slate-500">Faculty handling your enrolled subjects</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @forelse ($assignedInstructors as $instructor)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:border-violet-300 dark:border-slate-800 dark:bg-slate-800/50">
                        <span class="avatar" style="background: {{ $instructor->avatar_color }}22; color: {{ $instructor->avatar_color }}">{{ $instructor->initials() }}</span>
                        <span class="min-w-0">
                            <b class="block truncate text-sm">{{ $instructor->name }}</b>
                            <small class="block truncate text-xs text-slate-500">{{ $instructor->email }}</small>
                            <span class="mt-1 block text-xs font-semibold text-violet-600 dark:text-violet-400">
                                {{ $records->where('instructor_id', $instructor->id)->pluck('subject')->implode(', ') }}
                            </span>
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No instructors assigned to your records yet.</p>
                @endforelse
            </div>
        </section>

        {{-- AI chatbot --}}
        @php
            $welcomeText = 'Hello '.\Illuminate\Support\Str::before($u->name, ' ').'! I am your CPSU-Hinigaran academic coach. Ask me about attendance, quizzes, exams, projects, labs, your prediction, or how to improve.';
            $chatChips = ['How can I improve?', 'What is my prediction?', 'Tips for quizzes', 'Why am I at risk?'];
        @endphp
        <section id="chatbot" class="panel scroll-mt-24"
                 x-data="{
                     q: '',
                     loading: false,
                     messages: [{ role: 'bot', text: @json($welcomeText) }],
                     chips: @json($chatChips),
                     async ask(text) {
                         const question = (typeof text === 'string' ? text : this.q).trim();
                         if (!question || this.loading) return;
                         this.messages.push({ role: 'user', text: question });
                         this.q = '';
                         this.loading = true;
                         try {
                             const res = await fetch(@json(route('chat.answer')), {
                                 method: 'POST',
                                 headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                                 body: JSON.stringify({ question }),
                             });
                             const data = await res.json();
                             this.messages.push({ role: 'bot', text: data.answer || 'Sorry, I could not answer that — try rephrasing.' });
                         } catch (e) {
                             this.messages.push({ role: 'bot', text: 'Connection error — please try again.' });
                         }
                         this.loading = false;
                         this.$nextTick(() => { const el = this.$refs.log; if (el) el.scrollTop = el.scrollHeight; });
                     },
                 }">
            <div class="mb-4 flex items-center justify-between">
                <div><h2 class="section-title">AI study chatbot</h2><p class="text-sm text-slate-500">Rule-based coach powered by your live indicators</p></div>
                <span class="live-dot">Online</span>
            </div>

            <div x-ref="log" class="flex max-h-80 min-h-40 flex-col gap-2.5 overflow-y-auto rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/50">
                <template x-for="(m, i) in messages" :key="i">
                    <div :class="m.role === 'bot' ? 'self-start' : 'self-end'">
                        <template x-if="m.role === 'bot'"><div class="chat-in" x-text="m.text"></div></template>
                        <template x-if="m.role === 'user'"><div class="chat-out" x-text="m.text"></div></template>
                    </div>
                </template>
                <p x-show="loading" x-cloak class="self-start text-xs font-semibold text-slate-400">Coach is typing…</p>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="chip in chips" :key="chip">
                    <button type="button" @click="ask(chip)"
                            class="rounded-full border border-violet-200 px-3 py-1.5 text-xs font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white dark:border-violet-800 dark:text-violet-300"
                            x-text="chip"></button>
                </template>
            </div>

            <form @submit.prevent="ask()" class="mt-4 flex gap-2">
                <input x-model="q" class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800"
                       placeholder="e.g. How can I raise my final grade?" autocomplete="off">
                <button class="btn-primary" :disabled="loading" :class="loading ? 'opacity-60' : ''" x-text="loading ? '…' : 'Ask'"></button>
            </form>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (!window.Chart) return;

        const palette = ['#7c3aed', '#a855f7', '#c4b5fd', '#ddd6fe', '#f472b6', '#38bdf8', '#34d399', '#fbbf24'];
    const grid = { color: 'rgba(148,163,184,.15)' };
    const ticks = { color: '#94a3b8', font: { size: 11, weight: 600 } };

        const doughnutChart = new Chart(document.getElementById('doughnutChart'), {
        type: 'doughnut',
        data: {
            labels: @json($counts->keys()),
            datasets: [{ data: @json($counts->values()), backgroundColor: ['#10b981', '#f59e0b', '#f43f5e', '#cbd5e1'], borderWidth: 0 }],
        },
        options: { cutout: '70%', plugins: { legend: { display: false } }, maintainAspectRatio: false },
    });

        const barChart = new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: @json($indicatorAverages->pluck('label')),
            datasets: [{ label: 'Average %', data: @json($indicatorAverages->pluck('value')), backgroundColor: '#8b5cf6', hoverBackgroundColor: '#7c3aed', borderRadius: 8, maxBarThickness: 34 }],
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100, grid, ticks }, x: { ticks, grid: { display: false } } },
            plugins: { legend: { display: false } },
        },
    });

        const lineChart = new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: @json($trendLabels),
            datasets: [{
                label: 'Records added',
                data: @json($trendCounts),
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,.12)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#7c3aed',
                pointRadius: 4,
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid, ticks: { ...ticks, stepSize: 1 } },
                x: { ticks, grid: { display: false } },
            },
            plugins: { legend: { display: false } },
        },
    });

        const pieChart = new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: @json($sectionCounts->keys()),
            datasets: [{ data: @json($sectionCounts->values()), backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { color: '#64748b', font: { size: 11, weight: 600 }, boxWidth: 14 } } },
        },
    });

        const refreshAnalytics = async () => {
            try {
                const response = await fetch(@json(route('dashboard.analytics')), {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) return;

                const data = await response.json();
                const counts = data.counts || {};
                doughnutChart.data.datasets[0].data = Object.values(counts);
                doughnutChart.update();
                barChart.data.datasets[0].data = (data.indicatorAverages || []).map(item => item.value);
                barChart.update();
                lineChart.data.datasets[0].data = data.trendCounts || [];
                lineChart.update();
                pieChart.data.labels = data.sectionLabels || [];
                pieChart.data.datasets[0].data = Object.values(data.sectionCounts || {});
                pieChart.update();

                const monitored = document.getElementById('metric-monitored');
                if (monitored) monitored.textContent = data.monitoredStudents ?? monitored.textContent;
                const metricMap = {
                    passed: 'Passed',
                    'at-risk': 'At Risk',
                    failed: 'Failed',
                    'no-prediction-yet': 'No Prediction yet',
                };
                Object.entries(metricMap).forEach(([slug, status]) => {
                    const element = document.querySelector(`[data-metric="${slug}"]`);
                    if (element && counts[status] !== undefined) element.textContent = counts[status];
                });
            } catch {
                // Keep the last good chart data if a transient request fails.
            }
        };

        window.setInterval(refreshAnalytics, 60000);
    });
</script>
@endpush