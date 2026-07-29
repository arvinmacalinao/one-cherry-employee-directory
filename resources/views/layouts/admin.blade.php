<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Admin') . ' · ' . \App\Models\Setting::get('app_name', config('app.name')) }}</title>
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('oced-theme') ?? '');
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg font-body text-ink antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex min-h-screen">
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            style="display: none;"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-60 flex-col gap-4 border-r border-line bg-surface p-3.5 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center gap-2.5 px-1.5 pt-1.5">
                <div class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-[10px] bg-brand font-display text-base font-bold text-on-brand">
                    OC
                </div>
                <div class="min-w-0 leading-tight">
                    <p class="truncate font-display text-sm font-bold">One Cherry</p>
                    <p class="truncate text-[11px] text-ink-secondary">Employee Directory</p>
                </div>
            </div>
            <span class="w-fit rounded-md bg-brand-tint px-2.5 py-1 text-[10px] font-bold tracking-wide text-brand uppercase">Admin Panel</span>

            <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-grip w-4.5 text-center"></i> Dashboard
                </a>
                <a href="{{ route('admin.employees.index') }}" class="nav-item {{ request()->routeIs('admin.employees.*') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-users w-4.5 text-center"></i> Employees
                </a>
                <a href="{{ route('admin.companies.index') }}" class="nav-item {{ request()->routeIs('admin.companies.*') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-building w-4.5 text-center"></i> Companies
                </a>
                <a href="{{ route('admin.departments.index') }}" class="nav-item {{ request()->routeIs('admin.departments.*') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-sitemap w-4.5 text-center"></i> Departments
                </a>
                <a href="{{ route('admin.designations.index') }}" class="nav-item {{ request()->routeIs('admin.designations.*') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-award w-4.5 text-center"></i> Designations
                </a>
                <a href="{{ route('admin.offices.index') }}" class="nav-item {{ request()->routeIs('admin.offices.*') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-location-dot w-4.5 text-center"></i> Office Locations
                </a>
                <a href="{{ route('admin.announcements.index') }}" class="nav-item {{ request()->routeIs('admin.announcements.*') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-bullhorn w-4.5 text-center"></i> Announcements
                </a>

                <div class="my-2.5 h-px bg-line"></div>

                <a href="{{ route('admin.sync') }}" class="nav-item {{ request()->routeIs('admin.sync') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-database w-4.5 text-center"></i> HR Synchronization
                    @if ($unmappedCount ?? 0)
                        <span class="ml-auto flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-warning px-1 text-[10px] font-bold text-white">{{ $unmappedCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-gear w-4.5 text-center"></i> Settings
                </a>
                <a href="{{ route('admin.audit.index') }}" class="nav-item {{ request()->routeIs('admin.audit.*') ? 'nav-item-active' : '' }}">
                    <i class="fa-solid fa-shield-halved w-4.5 text-center"></i> Audit Logs
                </a>
            </nav>

            <div class="flex flex-col gap-2 border-t border-line pt-2.5">
                <a href="{{ route('home') }}" class="nav-item">
                    <i class="fa-solid fa-arrow-left w-4.5 text-center"></i> Exit to Directory
                </a>
                <button
                    type="button"
                    x-data="{
                        theme: localStorage.getItem('oced-theme') || 'system',
                        toggle() {
                            const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
                                || (document.documentElement.getAttribute('data-theme') === '' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                            this.theme = isDark ? 'light' : 'dark';
                            document.documentElement.setAttribute('data-theme', this.theme);
                            localStorage.setItem('oced-theme', this.theme);
                        }
                    }"
                    @click="toggle()"
                    class="flex items-center gap-2 rounded-lg border border-line bg-surface-raised px-2.5 py-2 text-sm font-medium text-ink-secondary hover:text-ink"
                >
                    <i class="fa-solid" :class="theme === 'dark' ? 'fa-moon' : 'fa-sun'"></i>
                    <span x-text="theme === 'dark' ? 'Dark mode' : 'Light mode'"></span>
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-15 items-center gap-3.5 border-b border-line bg-bg px-5 lg:px-7">
                <button @click="sidebarOpen = true" class="rounded-lg p-1.5 text-ink-secondary hover:bg-surface lg:hidden">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="font-display text-base">{{ $header ?? '' }}</h1>
                <div class="flex-1"></div>
                @auth
                    <span class="flex h-8.5 w-8.5 items-center justify-center rounded-full bg-brand font-display text-xs font-bold text-on-brand" title="{{ auth()->user()->name }}">
                        {{ collect(explode(' ', auth()->user()->name))->map(fn ($p) => $p[0])->take(2)->implode('') }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="flex h-8.5 w-8.5 items-center justify-center rounded-lg text-ink-secondary hover:bg-surface hover:text-ink" title="Sign out">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                @endauth
            </header>

            <main class="mx-auto w-full max-w-[1360px] flex-1 px-5 py-7 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
