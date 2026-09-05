<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Track a delivery · {{ config('app.name', 'Delivery Operations') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-3xl items-center gap-3 px-5 py-4 sm:px-8"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-600 text-sm font-extrabold text-white">DO</span><span><span class="block font-display text-sm font-bold">Delivery Ops</span><span class="block text-xs text-slate-500">Delivery tracking</span></span></div></header>
        <main class="mx-auto max-w-3xl px-5 py-10 sm:px-8">{{ $slot }}</main>
    </body>
</html>
