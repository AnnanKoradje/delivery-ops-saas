<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Delivery Operations') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased" x-data="{ mobileMenuOpen: false }" @keydown.escape.window="mobileMenuOpen = false">
        <div class="min-h-screen lg:flex">
            <aside class="hidden w-72 shrink-0 flex-col border-r border-slate-800 bg-slate-950 px-5 py-6 lg:flex">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-500 text-sm font-extrabold text-white shadow-lg shadow-blue-900/40">DO</span>
                    <span><span class="block font-display text-sm font-bold tracking-tight text-white">Delivery Ops</span><span class="block text-xs text-slate-400">SaaS Command Centre</span></span>
                </a>
                <nav class="mt-10 space-y-1" aria-label="Primary navigation">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('dashboard') || request()->routeIs('platform.dashboard') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm12 0h6V11h-6v10Zm0-18v4h6V3h-6Z" /></svg>Overview
                    </a>
                    @if (auth()->user()->isSuperAdmin())
                        <span class="mt-8 block px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Platform</span>
                        @can('manage-platform-companies')
                            <a href="{{ route('platform.companies.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('platform.companies.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>Companies</a>
                        @endcan
                    @else
                        <span class="mt-8 block px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Workspace</span>
                        @if (auth()->user()->hasPermission('company.profile.manage'))
                            <a href="{{ route('tenant.profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.profile.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 21h16M6 21V7l6-4 6 4v14M9 21v-5h6v5" /></svg>Company profile</a>
                        @endif
                        @if (auth()->user()->company?->settings && auth()->user()->can('view', auth()->user()->company->settings))
                            <a href="{{ route('tenant.settings.show', auth()->user()->company->settings) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.settings.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20h16M6 16h12M8 12h8M10 8h4M12 4v4" /></svg>Terminology</a>
                        @endif
                        @can('manage-company-staff')
                            <a href="{{ route('tenant.staff.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.staff.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>Staff access</a>
                        @endcan
                        @can('manage-delivery-personnel')
                            <a href="{{ route('tenant.personnel.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.personnel.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 21h14M7 21V9l5-4 5 4v12M10 21v-5h4v5" /></svg>{{ auth()->user()->company?->settings?->personnel_label_plural ?? 'Personnel' }}</a>
                        @endcan
                        @can('manage-operations')
                            <a href="{{ route('tenant.customers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.customers.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2M12 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z" /></svg>Customers</a>
                            <a href="{{ route('tenant.deliveries.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.deliveries.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h18M5 7v13h14V7M8 3h8v4M8 12h8M8 16h5" /></svg>{{ auth()->user()->company?->settings?->operation_label_plural ?? 'Operations' }}</a>
                            <a href="{{ route('tenant.operations.reports') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.operations.reports') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5M8 19v-7M12 19V9M16 19V4M20 19v-9" /></svg>Reports</a>
                        @endcan
                        @can('view-assigned-operations')
                            <a href="{{ route('tenant.assigned-deliveries.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tenant.assigned-deliveries.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12h16M12 4v16M5.5 5.5l13 13M18.5 5.5l-13 13" /></svg>My work</a>
                        @endcan
                    @endif
                </nav>
                <div class="mt-auto rounded-2xl border border-slate-800 bg-slate-900/70 p-4"><p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Signed in as</p><p class="mt-2 truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p><p class="mt-1 truncate text-xs text-slate-400">{{ auth()->user()->isSuperAdmin() ? 'Super Admin' : auth()->user()->company?->name }}</p><form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="text-xs font-semibold text-blue-300 transition hover:text-white">Sign out</button></form></div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="border-b border-slate-200 bg-white/90 px-5 py-4 backdrop-blur sm:px-8 lg:px-10">
                    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 lg:hidden"><span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-500 text-xs font-extrabold text-white">DO</span><span class="font-display text-sm font-bold">Delivery Ops</span></a>
                        <div class="min-w-0 flex-1">{{ $header ?? '' }}</div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('profile.edit') }}" class="hidden shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 sm:block">Account</a>
                            <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:hidden" @click="mobileMenuOpen = true" aria-label="Open navigation menu" :aria-expanded="mobileMenuOpen.toString()">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                            </button>
                        </div>
                    </div>
                </header>

                <div x-cloak x-show="mobileMenuOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Mobile navigation">
                    <div x-show="mobileMenuOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="mobileMenuOpen = false"></div>
                    <aside x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex h-full w-[min(20rem,86vw)] flex-col bg-slate-950 px-5 py-6 text-white shadow-2xl">
                        <div class="flex items-center justify-between"><a href="{{ route('dashboard') }}" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-500 text-sm font-extrabold">DO</span><span><span class="block text-sm font-bold">Delivery Ops</span><span class="block text-xs text-slate-400">SaaS Command Centre</span></span></a><button type="button" class="grid h-10 w-10 place-items-center rounded-xl text-slate-300 hover:bg-white/10 hover:text-white" @click="mobileMenuOpen = false" aria-label="Close navigation menu"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" /></svg></button></div>
                        <nav class="mt-10 space-y-1" aria-label="Mobile primary navigation">
                            <a href="{{ route('dashboard') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm12 0h6V11h-6v10Zm0-18v4h6V3h-6Z" /></svg>Overview</a>
                            @if (auth()->user()->isSuperAdmin())
                                <span class="mt-8 block px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Platform</span>@can('manage-platform-companies')<a href="{{ route('platform.companies.index') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>Companies</a>@endcan
                            @else
                                <span class="mt-8 block px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Workspace</span>
                                @if (auth()->user()->hasPermission('company.profile.manage'))<a href="{{ route('tenant.profile.edit') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Company profile</a>@endif
                                @if (auth()->user()->company?->settings && auth()->user()->can('view', auth()->user()->company->settings))<a href="{{ route('tenant.settings.show', auth()->user()->company->settings) }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Terminology</a>@endif
                                @can('manage-company-staff')<a href="{{ route('tenant.staff.index') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Staff access</a>@endcan
                                @can('manage-delivery-personnel')<a href="{{ route('tenant.personnel.index') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">{{ auth()->user()->company?->settings?->personnel_label_plural ?? 'Personnel' }}</a>@endcan
                                @can('manage-operations')<a href="{{ route('tenant.customers.index') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Customers</a><a href="{{ route('tenant.deliveries.index') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">{{ auth()->user()->company?->settings?->operation_label_plural ?? 'Operations' }}</a><a href="{{ route('tenant.operations.reports') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Reports</a>@endcan
                                @can('view-assigned-operations')<a href="{{ route('tenant.assigned-deliveries.index') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">My work</a>@endcan
                            @endif
                        </nav>
                        <div class="mt-auto rounded-2xl border border-slate-800 bg-slate-900/70 p-4"><p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Signed in as</p><p class="mt-2 truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p><p class="mt-1 truncate text-xs text-slate-400">{{ auth()->user()->isSuperAdmin() ? 'Super Admin' : auth()->user()->company?->name }}</p><form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="min-h-11 text-xs font-semibold text-blue-300 transition hover:text-white">Sign out</button></form></div>
                    </aside>
                </div>

                <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-10">{{ $slot }}</main>
            </div>
        </div>
    </body>
</html>
