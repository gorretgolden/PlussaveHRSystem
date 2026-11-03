<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · PlusSave HR</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite('resources/css/app.css')
</head>
<body class="grid min-h-full place-items-center bg-paper px-4 font-sans text-ink antialiased">
    <div class="w-full max-w-md rounded-2xl border border-line bg-surface p-6 text-center shadow-sm sm:p-8">
        <span class="mx-auto grid size-12 place-items-center rounded-xl bg-brand text-lg font-black text-white">P</span>
        <p class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-muted">{{ $code }}</p>
        <h1 class="mt-1 font-display text-2xl font-semibold">{{ $title }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $message }}</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
           class="mt-5 inline-flex rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">
            {{ auth()->check() ? 'Back to dashboard' : 'Go to sign in' }}
        </a>
    </div>
</body>
</html>
