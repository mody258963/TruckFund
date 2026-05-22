@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('app.name') }} — {{ __('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|noto-sans-arabic:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-full bg-zinc-50 antialiased dark:bg-zinc-950">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 border-e border-zinc-200 bg-zinc-900 text-white lg:block dark:border-zinc-800">
            <div class="flex h-16 items-center gap-2 border-b border-zinc-800 px-6">
                <span class="text-lg font-semibold text-teal-400">{{ __('app.name') }}</span>
            </div>
            <nav class="space-y-1 p-4 text-sm">
                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard') ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.dashboard') }}</a>
                <a href="{{ route('leads.index') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('leads.*') ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.leads') }}</a>
                <a href="{{ route('customers.index') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('customers.*') ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.customers') }}</a>
                <a href="{{ route('finance.index') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('finance.*') ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.finance') }}</a>
                <p class="px-3 pt-4 text-xs uppercase tracking-wider text-zinc-500">{{ __('nav.catalog') }}</p>
                <a href="{{ route('admin.catalog', 'merchants') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request('type') === 'merchants' ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.merchants') }}</a>
                <a href="{{ route('admin.catalog', 'financial-products') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request('type') === 'financial-products' ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.financial_products') }}</a>
                <a href="{{ route('admin.catalog', 'auto-products') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request('type') === 'auto-products' ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.auto_products') }}</a>
                <a href="{{ route('admin.catalog', 'suppliers') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request('type') === 'suppliers' ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.suppliers') }}</a>
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('audit.index') }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('audit.*') ? 'bg-zinc-800 text-teal-400' : '' }}">{{ __('nav.audit') }}</a>
                @endif
            </nav>
        </aside>
        <div class="flex flex-1 flex-col">
            <header class="flex h-16 items-center justify-between gap-4 border-b border-zinc-200 bg-white px-6 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-1 items-center gap-4">
                    @isset($header)
                        {{ $header }}
                    @endisset
                </div>
                <div class="flex items-center gap-2">
                    <livewire:global-search />
                    <livewire:locale-switcher />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:button type="submit" variant="ghost" size="sm">{{ __('auth.logout') }}</flux:button>
                    </form>
                </div>
            </header>
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
    @fluxScripts
</body>
</html>
