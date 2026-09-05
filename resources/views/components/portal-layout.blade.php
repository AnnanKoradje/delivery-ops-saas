<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Customer portal · {{ config('app.name', 'Delivery Operations') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-8"><a href="{{ auth('customer')->check() ? route('portal.deliveries.index') : route('portal.login') }}" class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-600 text-sm font-extrabold text-white">DO</span><span><span class="block font-display text-sm font-bold">Delivery Ops</span><span class="block text-xs text-slate-500">Customer portal</span></span></a>@auth('customer')<nav class="flex items-center gap-2"><a href="{{ route('portal.deliveries.index') }}" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block">Deliveries</a><a href="{{ route('portal.profile.edit') }}" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Profile</a><form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="rounded-xl bg-slate-950 px-3 py-2 text-sm font-semibold text-white">Sign out</button></form></nav>@endauth</div></header>
        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8">{{ $slot }}</main>
    </body>
</html>
