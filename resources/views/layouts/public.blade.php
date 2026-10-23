<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Local Phone Numbers') . ' · ' . \App\Models\Setting::get('app_name', config('app.name')) }}</title>
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('oced-theme') ?? '');
        // Printing: always the light palette, and the sheet scaled to exactly one
        // A4 page. `.printing` switches on the compact print layout so it can be
        // measured here, before the browser lays out the printout.
        (() => {
            const mm = 96 / 25.4;
            const pageWidth = 190 * mm;          // A4 minus the 10mm @page margins
            const pageHeight = 277 * mm * 0.97;  // small safety margin against rounding

            window.addEventListener('beforeprint', () => {
                const root = document.documentElement;
                root.setAttribute('data-theme', 'light');
                root.classList.add('printing');

                const area = document.getElementById('phone-print-area');
                if (! area) return;

                // Narrower page → taller sheet → more shrink; a few passes settle it.
                let scale = 1;
                area.style.zoom = '';
                for (let i = 0; i < 4; i++) {
                    area.style.width = (pageWidth / scale) + 'px';
                    scale = Math.min(1, pageHeight / area.offsetHeight);
                }
                area.style.width = (pageWidth / scale) + 'px';
                scale = Math.min(scale, pageHeight / area.offsetHeight);
                area.style.zoom = scale;
            });

            window.addEventListener('afterprint', () => {
                const root = document.documentElement;
                root.classList.remove('printing');
                root.setAttribute('data-theme', localStorage.getItem('oced-theme') ?? '');

                const area = document.getElementById('phone-print-area');
                if (area) {
                    area.style.width = '';
                    area.style.zoom = '';
                }
            });
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg font-body text-ink antialiased">

    <header class="sticky top-0 z-20 border-b border-line bg-bg print:hidden">
        <div class="mx-auto flex h-15 w-full max-w-[1360px] items-center gap-3 px-5 lg:px-8">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
                <span class="flex h-8.5 w-8.5 flex-shrink-0 items-center justify-center rounded-[10px] bg-brand font-display text-base font-bold text-on-brand">OC</span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate font-display text-sm font-bold">One Cherry</span>
                    <span class="block truncate text-[11px] text-ink-secondary">Local Phone Numbers</span>
                </span>
            </a>

            <div class="flex-1"></div>

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
                class="flex h-8.5 w-8.5 items-center justify-center rounded-lg text-ink-secondary hover:bg-surface hover:text-ink"
                title="Toggle theme"
            >
                <i class="fa-solid" :class="theme === 'dark' ? 'fa-moon' : 'fa-sun'"></i>
            </button>

            @auth
                @can('view employee directory')
                    <a href="{{ route('dashboard') }}" wire:navigate class="btn-ghost hidden text-xs sm:inline-flex"><i class="fa-solid fa-address-book"></i>Employee Directory</a>
                @endcan
                <a href="{{ auth()->user()->homeUrl() }}" class="btn-secondary text-xs"><i class="fa-solid fa-gear"></i>Admin</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex h-8.5 w-8.5 items-center justify-center rounded-lg text-ink-secondary hover:bg-surface hover:text-ink" title="Sign out">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            @endauth
            {{-- No sign-in link for guests on purpose — staff go to /login directly. --}}
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1360px] px-5 py-7 lg:px-8 print:max-w-none print:p-0">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
