<!doctype html>
<html lang="en" x-data="shell">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="server-time" content="{{ now()->toIso8601String() }}">
    <link rel="icon" href="{{ asset('images/campus-logo.svg') }}" type="image/svg+xml">
    <title>{{ $title ?? 'Dashboard' }} | CPSU-Hinigaran Predictor</title>
    <script>try { if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}</script>
    @include('partials.assets')
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
@php
    $user = auth()->user();
    $unread = \App\Models\Message::where('recipient_id', $user->id)->whereNull('read_at')->count();
@endphp

<div class="min-h-screen">
    @include('partials.sidebar')

    <main class="transition-all duration-300" :class="sidebar ? (collapsed ? 'lg:pl-24' : 'lg:pl-64') : ''">
        @include('partials.header')

        <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition class="animate-rise mb-5 flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">
                    <span>{{ session('success') }}</span>
                    <button type="button" @click="show = false" class="font-black" aria-label="Dismiss">&times;</button>
                </div>
            @endif
            @if ($errors->any())
                <div class="animate-rise mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </div>

        <footer class="border-t border-slate-200 px-6 py-5 text-center text-xs text-slate-400 dark:border-slate-800">
            © {{ date('Y') }} CPSU-Hinigaran Academic Performance Predictor · College of Computer Studies ·
            <span x-text="now"></span>
        </footer>
    </main>
</div>
@stack('scripts')
</body>
</html>