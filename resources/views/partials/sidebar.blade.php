@php
    $icons = [
        'dashboard' => '<path d="M3 12l9-9 9 9M5 10v10h4v-6h6v6h4V10"/>',
        'students' => '<path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'instructors' => '<path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 10-4-4 4 4 0 004 4z"/>',
        'adduser' => '<path d="M18 9v3m0 0v3m0-3h-3m3 0h3M8 9a3 3 0 11-6 0 3 3 0 016 0zM2 20a6 6 0 0112 0v1H2v-1z"/>',
        'logs' => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'messages' => '<path d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4-4 7-9 7a10 10 0 01-4-.8L3 20l1.4-3.7A6.7 6.7 0 013 12c0-4 4-7 9-7s9 3 9 7z"/>',
        'profile' => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'records' => '<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.59l5.41 5.41V19a2 2 0 01-2 2z"/>',
        'chat' => '<path d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4-4 7-9 7a10 10 0 01-4-.8L3 20l1.4-3.7A6.7 6.7 0 013 12c0-4 4-7 9-7s9 3 9 7z"/>',
    ];
    $nav = [
        'admin' => [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'admin.students', 'label' => 'Manage students', 'icon' => 'students'],
            ['route' => 'admin.instructors', 'label' => 'Manage instructors', 'icon' => 'instructors'],
            ['route' => 'admin.users', 'label' => 'Add users', 'icon' => 'adduser'],
            ['route' => 'admin.logs', 'label' => 'System logs', 'icon' => 'logs'],
            ['route' => 'messages.index', 'label' => 'Messages', 'icon' => 'messages'],
            ['route' => 'profile.edit', 'label' => 'Profile settings', 'icon' => 'profile'],
        ],
        'instructor' => [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'dashboard', 'label' => 'Add students', 'icon' => 'records', 'anchor' => '#record-form'],
            ['route' => 'dashboard', 'label' => 'Import CSV', 'icon' => 'adduser', 'anchor' => '#import-csv'],
            ['route' => 'messages.index', 'label' => 'Message at-risk', 'icon' => 'messages'],
            ['route' => 'profile.edit', 'label' => 'Profile settings', 'icon' => 'profile'],
        ],
        'student' => [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'dashboard', 'label' => 'My progress', 'icon' => 'records', 'anchor' => '#records'],
            ['route' => 'dashboard', 'label' => 'AI chatbot', 'icon' => 'chat', 'anchor' => '#chatbot'],
            ['route' => 'messages.index', 'label' => 'Messages', 'icon' => 'messages'],
            ['route' => 'profile.edit', 'label' => 'Profile settings', 'icon' => 'profile'],
        ],
    ];

    $dashboardRoute = match ($user->role) {
        'admin' => 'admin.dashboard',
        'instructor' => 'instructor.dashboard',
        default => 'student.dashboard',
    };
    $nav[$user->role][0]['route'] = $dashboardRoute;
@endphp

<div x-show="sidebar" @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-300 dark:border-slate-800 dark:bg-slate-900 lg:transition-[width]"
       :class="{ 'translate-x-0': sidebar, '-translate-x-full': !sidebar, 'lg:w-64': !collapsed, 'lg:w-24': collapsed }">
    <div class="flex items-center justify-between gap-2 px-4 py-5" :class="collapsed ? 'lg:justify-center lg:px-2' : ''">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            <span class="brand-mark !h-10 !w-10"><img class="h-8 w-8" src="{{ asset('images/campus-logo.svg') }}" alt="CPSU campus logo"></span>
            <span x-show="!collapsed" class="min-w-0">
                <b class="brand-display block truncate text-sm">CPSU-Hinigaran</b>
                <small class="block truncate text-xs text-slate-500">Performance Predictor</small>
            </span>
        </a>
        <button type="button" @click="toggleCollapsed()" class="icon-btn hidden shrink-0 lg:grid" aria-label="Collapse sidebar">
            <svg class="h-4 w-4 transition-transform" :class="collapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
        @foreach ($nav[$user->role] as $item)
            @php($isActive = empty($item['anchor']) && request()->routeIs($item['route']))
            <a href="{{ route($item['route']).($item['anchor'] ?? '') }}"
               title="{{ $item['label'] }}"
               class="sidebar-item {{ $isActive ? 'sidebar-item-active' : '' }}"
               :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">{!! $icons[$item['icon']] !!}</svg>
                <span x-show="!collapsed" class="whitespace-nowrap">{{ $item['label'] }}</span>
                @if ($item['label'] === 'Messages' && $unread > 0)
                    <span class="ml-auto rounded-full px-1.5 text-[10px] font-black {{ $isActive ? 'bg-white/25 text-white' : 'bg-violet-600 text-white' }}">{{ $unread }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="px-3 pb-5">
        <div x-show="!collapsed" class="rounded-2xl bg-violet-50 p-4 text-xs text-violet-900 dark:bg-violet-950/50 dark:text-violet-200">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/ccs-logo.svg') }}" alt="CCS logo" class="h-7 w-7 rounded-full bg-white p-0.5">
                <b>College of Computer Studies</b>
            </div>
            <p class="mt-1.5 opacity-70">Predict · Support · Succeed — helping every learner move forward.</p>
        </div>
    </div>
</aside>