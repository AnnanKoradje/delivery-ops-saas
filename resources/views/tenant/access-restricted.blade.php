<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-600">Company workspace</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ auth()->user()->company->name }}</h1></div></x-slot>
    <div class="rounded-2xl border border-rose-100 bg-rose-50 p-8 shadow-sm"><h2 class="text-lg font-semibold text-slate-950">Workspace access is currently unavailable.</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">This company workspace is {{ auth()->user()->company->status }}. Operational routes remain blocked until a platform administrator restores active status.</p></div>
</x-app-layout>
