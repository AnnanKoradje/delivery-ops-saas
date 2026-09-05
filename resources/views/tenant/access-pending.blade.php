<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Company workspace</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ auth()->user()->company->name }}</h1></div></x-slot>
    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-8 shadow-sm"><h2 class="text-lg font-semibold text-slate-950">Your company application is awaiting approval.</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">A platform administrator must approve the company before Company Admin access can be activated. Tenant operational routes remain unavailable until that review is complete.</p></div>
</x-app-layout>
