<!doctype html>
<html lang="en" class="scroll-smooth" x-data="shell">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="server-time" content="{{ now()->toIso8601String() }}">
    <link rel="icon" href="{{ asset('images/campus-logo.svg') }}" type="image/svg+xml">
    <title>CPSU-Hinigaran Academic Performance Predictor</title>
    <script>try { if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}</script>
    @include('partials.assets')
</head>
<body class="bg-white text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <nav class="fixed z-50 w-full border-b border-violet-100 bg-white/85 backdrop-blur-md dark:border-violet-900/60 dark:bg-slate-950/85">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/campus-logo.svg') }}" alt="CPSU campus logo" class="h-10 w-10 rounded-full bg-violet-700 p-0.5">
                <img src="{{ asset('images/ccs-logo.svg') }}" alt="CCS logo" class="-ml-5 h-10 w-10 rounded-full bg-violet-500 p-0.5 ring-2 ring-white dark:ring-slate-950">
                <span class="brand-display hidden text-sm font-black tracking-tight text-violet-800 dark:text-violet-300 sm:block">Academic Performance Predictor</span>
            </a>
            <div class="flex items-center gap-2 sm:gap-4">
                <span class="hidden text-xs font-semibold text-slate-500 lg:block" x-text="now"></span>
                <button @click="toggleTheme()" class="rounded-full p-2.5 transition hover:bg-violet-100 dark:hover:bg-violet-950" aria-label="Toggle dark mode">
                    <svg x-show="!dark" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <a href="{{ route('login') }}" class="rounded-xl bg-violet-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-violet-300 transition hover:-translate-y-0.5 hover:bg-violet-600 dark:shadow-none">Login</a>
            </div>
        </div>
    </nav>

    <section class="gradient-bg hero-grid px-4 pb-20 pt-28 text-white sm:pt-36">
        <div class="mx-auto max-w-5xl text-center">
            <p class="eyebrow animate-rise !text-violet-200">Central Philippines State University · Hinigaran Campus · College of Computer Studies</p>
            <h1 class="brand-display animate-rise animate-delay-1 mb-6 mt-5 text-4xl font-black leading-tight md:text-6xl">Predict. Support. Succeed.</h1>
            <p class="animate-rise animate-delay-2 mx-auto mb-9 max-w-3xl text-lg opacity-90 md:text-2xl">
                A smart academic performance prediction system built for <strong>IT / Computer Studies students of CPSU-Hinigaran</strong> — turning attendance, quizzes, exams, projects and labs into clear Passed / At Risk / Failed guidance.
            </p>
            <div class="animate-rise animate-delay-3 flex flex-col justify-center gap-4 sm:flex-row">
                <a href="{{ route('login') }}" class="rounded-xl bg-white px-8 py-3.5 font-bold text-violet-800 shadow-xl transition hover:-translate-y-0.5 hover:bg-violet-50">Login to Dashboard</a>
                <a href="#overview" class="rounded-xl border-2 border-white/70 px-8 py-3.5 font-bold transition hover:bg-white/10">Explore the system</a>
            </div>
            <div class="mx-auto mt-12 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-white/10 p-4 backdrop-blur transition hover:bg-white/20"><b class="block text-2xl font-black">{{ number_format($stats['students']) }}</b><small class="text-xs text-violet-100">Students monitored</small></div>
                <div class="rounded-2xl bg-white/10 p-4 backdrop-blur transition hover:bg-white/20"><b class="block text-2xl font-black">{{ number_format($stats['records']) }}</b><small class="text-xs text-violet-100">Performance records</small></div>
                <div class="rounded-2xl bg-white/10 p-4 backdrop-blur transition hover:bg-white/20"><b class="block text-2xl font-black">{{ number_format($stats['Passed']) }}</b><small class="text-xs text-violet-100">Predicted Passed</small></div>
                <div class="rounded-2xl bg-white/10 p-4 backdrop-blur transition hover:bg-white/20"><b class="block text-2xl font-black">{{ number_format($stats['At Risk'] + $stats['Failed']) }}</b><small class="text-xs text-violet-100">Need intervention</small></div>
            </div>
        </div>
    </section>
    {{-- System overview: three roles --}}
    <section id="overview" class="bg-slate-50 py-20 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl px-4">
            <p class="eyebrow text-center">System overview</p>
            <h2 class="section-title mt-3 text-center text-3xl font-black">One platform, three role-based experiences</h2>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div class="card-hover group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-violet-100 text-2xl transition group-hover:scale-110 group-hover:bg-violet-600 group-hover:text-white dark:bg-violet-950">🧑‍💼</div>
                    <h3 class="text-xl font-black">Administrator</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">Full control: manage students &amp; instructors, create accounts, assign sections and subjects, watch live analytics, and audit every action in the system logs.</p>
                </div>
                <div class="card-hover group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-violet-100 text-2xl transition group-hover:scale-110 group-hover:bg-violet-600 group-hover:text-white dark:bg-violet-950">👩‍🏫</div>
                    <h3 class="text-xl font-black">Instructor</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">Scoped to your assigned subjects &amp; sections: add students manually or by CSV, record the eight performance indicators, and message learners who are At Risk or Failed.</p>
                </div>
                <div class="card-hover group rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-violet-100 text-2xl transition group-hover:scale-110 group-hover:bg-violet-600 group-hover:text-white dark:bg-violet-950">🎓</div>
                    <h3 class="text-xl font-black">Student</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">See your own progress, prediction result, enrolled subjects and instructors — plus an AI chatbot that tells you exactly how to improve.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4">
            <p class="eyebrow text-center">Prediction engine</p>
            <h2 class="section-title mt-3 text-center text-3xl font-black">How your prediction is calculated</h2>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div class="panel card-hover"><span class="eyebrow">Step 01</span><h3 class="mt-3 font-black">Eight indicators captured</h3><p class="mt-2 text-sm leading-6 text-slate-500">Attendance, quiz, midterm, final grade, exam, assignment, project output and laboratory activities are recorded per subject.</p></div>
                <div class="panel card-hover"><span class="eyebrow">Step 02</span><h3 class="mt-3 font-black">Weighted scoring</h3><p class="mt-2 text-sm leading-6 text-slate-500">Each indicator carries a weight (final grade 20%, attendance 15%…). Missing values are excluded fairly instead of counting as zero.</p></div>
                <div class="panel card-hover"><span class="eyebrow">Step 03</span><h3 class="mt-3 font-black">Clear status band</h3><p class="mt-2 text-sm leading-6 text-slate-500">75–100 → <span class="badge badge-passed">Passed</span> · 60–74.99 → <span class="badge badge-at-risk">At Risk</span> · below 60 → <span class="badge badge-failed">Failed</span> · no data → <span class="badge badge-no-prediction-yet">No Prediction yet</span></p></div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="bg-violet-50 py-16 dark:bg-violet-950/30">
        <div class="mx-auto max-w-7xl px-4">
            <h2 class="section-title text-center text-3xl font-black">Built for the CCS community</h2>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['📊', 'Live analytics', 'Doughnut, bar, line and pie charts refresh with every visit.'],
                    ['📥', 'CSV import', 'Bulk-add performance records with a downloadable template.'],
                    ['✉️', 'At-risk messaging', 'Instructors message every At Risk / Failed student in one action.'],
                    ['🕑', 'Real-time clock', 'Accurate client-side time plus server-side timestamps everywhere.'],
                    ['🛡️', 'System logs', 'Logins, logouts, edits and IPs — fully auditable.'],
                    ['🌙', 'Dark mode & responsive', 'Purple-white theme that shines on mobile, tablet and desktop.'],
                ] as [$emoji, $title, $copy])
                    <div class="card-hover flex gap-4 rounded-2xl border border-violet-100 bg-white p-5 dark:border-violet-900 dark:bg-slate-900">
                        <span class="text-2xl">{{ $emoji }}</span>
                        <div>
                            <b class="block text-sm font-black">{{ $title }}</b>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $copy }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    {{-- FAQ with live automated answers --}}
    <section class="py-20 dark:bg-slate-900/40"
             x-data="{
                 q: '',
                 open: null,
                 faqs: [
                     { Q: 'Who can use this system?', A: 'Only registered students, instructors and administrators of the College of Computer Studies, CPSU-Hinigaran. Accounts are created exclusively by the Administrator — there is no public registration.' },
                     { Q: 'How is my prediction calculated?', A: 'Eight indicators (attendance, quiz, midterm, final grade, exam, assignment, project output, laboratory) are combined with configured weights into one score: 75+ Passed, 60–74.99 At Risk, below 60 Failed. Missing indicators are excluded fairly rather than scored as zero.' },
                     { Q: 'What does At Risk mean?', A: 'You are close to the passing band but need intervention — focus on your lowest indicator, attend consultation, and submit outstanding work before finals.' },
                     { Q: 'Why do I see No Prediction yet?', A: 'Your subject has been assigned but no indicator values have been recorded yet. Once your instructor enters grades, the prediction appears automatically.' },
                     { Q: 'Can I import grades from a spreadsheet?', A: 'Yes — instructors can upload a CSV/XLSX file using the downloadable template with headers: name, student_id, email, subject, section, academic_year plus the eight indicators.' },
                     { Q: 'How do I recover my account?', A: 'Contact your administrator or the CCS office. Passwords can be reset from Profile settings once you are signed in.' },
                 ],
                 get matches() {
                     const t = this.q.toLowerCase().trim();
                     if (!t) return this.faqs;
                     return this.faqs.filter(f => (f.Q + ' ' + f.A).toLowerCase().includes(t));
                 },
             }">
        <div class="mx-auto max-w-3xl px-4">
            <p class="eyebrow text-center">Support</p>
            <h2 class="section-title mt-3 text-center text-3xl font-black">Frequently asked questions</h2>
            <div class="relative mt-8">
                <input x-model="q" type="search" placeholder='Type a question — e.g. "how is my prediction calculated?"'
                       class="input-focus w-full rounded-2xl border border-violet-200 bg-white px-5 py-4 text-sm outline-none transition dark:border-violet-900 dark:bg-slate-900">
                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-violet-400">🔍</span>
            </div>

            <div class="mt-6 space-y-3">
                <template x-for="(f, i) in matches" :key="f.Q">
                    <div class="faq-item">
                        <button type="button" @click="open = open === i ? null : i" class="flex w-full items-center justify-between gap-4 px-6 py-4 text-left text-sm font-bold">
                            <span x-text="f.Q"></span>
                            <span class="text-lg text-violet-500" x-text="open === i ? '−' : '+'"></span>
                        </button>
                        <div x-show="open === i" x-cloak x-transition class="px-6 pb-5 text-sm leading-6 text-slate-500 dark:text-slate-400" x-text="f.A"></div>
                    </div>
                </template>
                <p x-show="matches.length === 0" x-cloak class="rounded-2xl border border-dashed border-violet-300 p-6 text-center text-sm text-slate-500">
                    No matching FAQ — email <b>ccs@cpsu.edu.ph</b> and we will answer personally.
                </p>
            </div>
        </div>
    </section>

    {{-- Contact --}}
    <section class="gradient-bg px-4 py-16 text-white">
        <div class="mx-auto grid max-w-5xl gap-8 text-center sm:grid-cols-3 sm:text-left">
            <div>
                <b class="block font-black">📍 Campus</b>
                <p class="mt-1 text-sm text-violet-100">{{ config('cpsu.department') }}<br>{{ config('cpsu.school') }} – {{ config('cpsu.campus') }}, {{ config('cpsu.address') }}</p>
            </div>
            <div>
                <b class="block font-black">✉️ Email</b>
                <p class="mt-1 text-sm text-violet-100">{{ config('cpsu.email') }}<br>icts@cpsu-hinigaran.edu.ph</p>
            </div>
            <div>
                <b class="block font-black">☎️ Phone</b>
                <p class="mt-1 text-sm text-violet-100">{{ config('cpsu.phone') }}<br>{{ config('cpsu.office_hours') }} (<span x-text="now"></span>)</p>
            </div>
        </div>
    </section>

    <footer class="bg-slate-950 py-8 text-center text-xs text-slate-400">
        <div class="mb-3 flex items-center justify-center gap-2">
            <img src="{{ asset('images/campus-logo.svg') }}" alt="CPSU campus logo" class="h-8 w-8 rounded-full">
            <img src="{{ asset('images/ccs-logo.svg') }}" alt="CCS logo" class="h-8 w-8 rounded-full">
            <span class="font-bold text-slate-300">CPSU-Hinigaran Academic Performance Predictor</span>
        </div>
        © {{ date('Y') }} Central Philippines State University · College of Computer Studies. All rights reserved.
    </footer>
</body>
</html>