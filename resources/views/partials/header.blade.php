<header class="sticky top-0 z-20 flex min-h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
    <div class="flex items-center gap-2">
        <button type="button" @click="toggleSidebar()" class="icon-btn" aria-label="Toggle sidebar">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        {{-- Real-time client-side date & time (updates every second) --}}
        <span class="hidden text-sm font-semibold text-slate-500 dark:text-slate-400 md:inline" x-text="now"></span>
        <span class="live-dot md:hidden">Live</span>
    </div>

    <div class="flex items-center gap-1 sm:gap-2">
        <button type="button" @click="toggleTheme()" class="icon-btn" aria-label="Toggle dark mode">
            <svg x-show="!dark" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>

        <a href="{{ route('messages.index') }}" class="icon-btn relative" aria-label="Messages" title="Messages">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            @if ($unread > 0)
                <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[9px] font-black text-white">{{ $unread }}</span>
            @endif
        </a>

        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-1.5 py-1 transition hover:bg-slate-100 dark:hover:bg-slate-800" title="Profile settings">
            <span class="hidden text-right sm:block">
                <b class="block max-w-40 truncate text-sm">{{ $user->name }}</b>
                <small class="block text-xs capitalize text-slate-500">{{ $user->role }}</small>
            </span>
            <span class="avatar" style="background: {{ $user->avatar_color }}22; color: {{ $user->avatar_color }}">{{ $user->initials() }}</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-500 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-rose-950/40">Log out</button>
        </form>
    </div>
</header>