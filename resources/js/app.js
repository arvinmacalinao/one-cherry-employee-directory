import './bootstrap';

// Theme persistence across wire:navigate — see architecture-plan.md §8.
// A <head> inline script still applies the theme pre-paint (avoids a flash on
// hard loads), but that script only runs once and is not guaranteed to re-fire
// on a Livewire SPA-style navigation. This listener is the single source of
// truth: it runs on the very first load *and* after every wire:navigate swap,
// so the explicitly-chosen theme never falls back to prefers-color-scheme
// just because the user clicked a link instead of hitting refresh.
function applyTheme() {
    document.documentElement.setAttribute('data-theme', localStorage.getItem('oced-theme') ?? '');
}

applyTheme();
document.addEventListener('livewire:navigated', applyTheme);
