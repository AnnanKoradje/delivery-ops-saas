<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Platform oversight</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Good morning, {{ auth()->user()->name }}</h1>
            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">Monitor tenant lifecycle health without entering individual company workspaces.</p>
        </div>
    </x-slot>

    <div class="space-y-8">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Registered companies', 'value' => $summary['total'], 'style' => 'bg-slate-50 text-slate-700'],
                ['label' => 'Pending review', 'value' => $summary['pending'], 'style' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Active workspaces', 'value' => $summary['active'], 'style' => 'bg-emerald-50 text-emerald-700'],
                ['label' => 'Suspended workspaces', 'value' => $summary['suspended'], 'style' => 'bg-rose-50 text-rose-700'],
            ] as $metric)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($metric['value']) }}</p>
                    <span class="mt-4 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $metric['style'] }}">Foundation metric</span>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/70">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <div><h2 class="font-semibold text-slate-950">Recently registered companies</h2><p class="mt-1 text-sm text-slate-500">Review applications and control workspace lifecycle.</p></div>
                    <a href="{{ route('platform.companies.index') }}" class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100">Manage applications</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentCompanies as $company)
                        <a href="{{ route('platform.companies.show', $company) }}" class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $company->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $company->business_type ?? 'Business category pending' }} · {{ $company->created_at->diffForHumans() }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $company->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($company->status === 'suspended' ? 'bg-rose-50 text-rose-700' : ($company->status === 'rejected' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700')) }}">{{ ucfirst($company->status) }}</span></a>
                    @empty
                        <div class="px-6 py-12 text-center"><p class="text-sm font-medium text-slate-700">No company applications yet</p><p class="mt-1 text-sm text-slate-500">Share the application route when you are ready to onboard a business.</p></div>
                    @endforelse
                </div>
            </article>

            <aside class="rounded-2xl bg-slate-950 p-6 text-white shadow-lg shadow-slate-300"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-300">Security posture</p><h2 class="mt-3 text-xl font-semibold tracking-tight">Tenant boundaries are enforced server-side.</h2><p class="mt-3 text-sm leading-6 text-slate-300">Company-scoped records inherit the authenticated company context. Platform roles do not receive implicit access to tenant operational records.</p><div class="mt-6 border-t border-slate-800 pt-5"><p class="text-sm font-semibold text-white">Controlled onboarding is active</p><p class="mt-1 text-sm leading-6 text-slate-400">Approve, reject, suspend, and issue one-time Company Admin invitations without relaxing tenant controls.</p></div></aside>
        </section>
    </div>
</x-app-layout>
