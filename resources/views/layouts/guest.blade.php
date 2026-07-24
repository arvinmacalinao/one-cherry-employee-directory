<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface font-body text-ink antialiased">
    <div class="flex min-h-screen items-center justify-center px-4">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
