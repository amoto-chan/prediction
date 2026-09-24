<!doctype html>
<html lang="en" x-data="shell">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="server-time" content="{{ now()->toIso8601String() }}">
    <link rel="icon" href="{{ asset('images/campus-logo.svg') }}" type="image/svg+xml">
    <title>Login | CPSU-Hinigaran Academic Performance Predictor</title>
    <script>try { if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}</script>
    @include('partials.assets')
</head>
<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-100 p-4 dark:bg-slate-950">
    <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-violet-400/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-16 h-80 w-80 rounded-full bg-fuchsia-400/20 blur-3xl"></div>

    <button @click="toggleTheme()" class="absolute right-5 top-5 z-50 rounded-full bg-white p-3 shadow-lg transition dark:bg-slate-800" aria-label="Toggle dark mode">
        <svg x-show="!dark" class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg x-show="dark" x-cloak class="h-5 w-5 text-amber-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    </button>

    <div class="w-full max-w-md" x-data="{ fill(email) { this.$refs.email.value = email; this.$refs.password.value = 'password'; } }">
        <div class="animate-rise overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
            <div class="gradient-bg hero-grid px-6 py-8 text-center text-white">
                <div class="mb-4 flex items-center justify-center gap-3">
                    <img src="{{ asset('images/campus-logo.svg') }}" alt="CPSU campus logo" class="h-14 w-14 rounded-full bg-white/20 p-1 backdrop-blur">
                    <img src="{{ asset('images/ccs-logo.svg') }}" alt="CCS logo" class="h-14 w-14 rounded-full bg-white/20 p-1 backdrop-blur">
                </div>
                <h1 class="brand-display text-2xl font-black">Academic Performance Predictor</h1>
                <p class="mt-1 text-sm text-violet-100">Central Philippines State University – Hinigaran Campus</p>
                <p class="mt-3 text-xs font-semibold uppercase tracking-[.2em] text-violet-200" x-text="now"></p>
            </div>

            <div class="p-7 sm:p-8">
                <h2 class="mb-5 text-center text-lg font-bold text-slate-800 dark:text-white">Sign in to your account</h2>

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                    @csrf
                    <label class="field">Email address
                        <input x-ref="email" type="email" name="email" value="{{ old('email') }}" required placeholder="you@cpsu-hinigaran.edu.ph">
                    </label>
                    <label class="field">Password
                        <input x-ref="password" type="password" name="password" required placeholder="••••••••">
                    </label>

                    <div class="rounded-xl bg-violet-50 p-3 dark:bg-violet-950/40">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-violet-500">Quick demo fill</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" @click="fill('admin@cpsu-hinigaran.edu.ph')" class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-violet-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-violet-600 hover:text-white dark:bg-slate-800 dark:text-violet-300">Administrator</button>
                            <button type="button" @click="fill('instructor@cpsu-hinigaran.edu.ph')" class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-violet-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-violet-600 hover:text-white dark:bg-slate-800 dark:text-violet-300">Instructor</button>
                            <button type="button" @click="fill('amara.dela.cruz@cpsu-hinigaran.edu.ph')" class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-violet-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-violet-600 hover:text-white dark:bg-slate-800 dark:text-violet-300">Student</button>
                        </div>
                        <p class="mt-2 text-[11px] text-violet-500">Demo password: <b>password</b></p>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-violet-700 py-3.5 font-bold text-white shadow-lg shadow-violet-500/30 transition hover:scale-[1.02] hover:bg-violet-600 active:scale-95">
                        Login to dashboard →
                    </button>
                </form>

                <p class="mt-5 text-center text-xs text-slate-500 dark:text-slate-400">Your role (Admin / Instructor / Student) is detected automatically.<br>Only accounts created by the Administrator can sign in.</p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-sm font-bold text-violet-700 transition hover:underline dark:text-violet-400">← Back to landing page</a>
        </div>
    </div>
</body>
</html>