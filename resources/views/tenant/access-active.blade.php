<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-600">Company workspace</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ auth()->user()->company->name }}</h1></div></x-slot>
    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-8 shadow-sm"><h2 class="text-lg font-semibold text-slate-950">Your workspace is active.</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Company-specific customers, delivery assignment, secure status progression, and an auditable operations timeline are now ready to use.</p></div>
    <div class="mt-6 grid gap-5 md:grid-cols-2">
        @can('manage-operations')
            <a href="{{ route('tenant.deliveries.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"><p class="text-xs font-semibold uppercase tracking-[.14em] text-blue-600">Operations board</p><h2 class="mt-2 text-lg font-semibold">Manage {{ auth()->user()->company?->settings?->operation_label_plural ?? 'deliveries' }}</h2><p class="mt-2 text-sm leading-6 text-slate-500">Create delivery records, allocate active personnel, and supervise lifecycle updates.</p></a>
            <a href="{{ route('tenant.customers.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"><p class="text-xs font-semibold uppercase tracking-[.14em] text-blue-600">Operations directory</p><h2 class="mt-2 text-lg font-semibold">Customers</h2><p class="mt-2 text-sm leading-6 text-slate-500">Keep customer contact details isolated to this company before scheduling deliveries.</p></a>
        @endcan
        @can('view-assigned-operations')
            <a href="{{ route('tenant.assigned-deliveries.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"><p class="text-xs font-semibold uppercase tracking-[.14em] text-blue-600">Field work</p><h2 class="mt-2 text-lg font-semibold">My assigned work</h2><p class="mt-2 text-sm leading-6 text-slate-500">View only the operations assigned to your linked personnel record and record the next safe status.</p></a>
        @endcan
    </div>
</x-app-layout>
