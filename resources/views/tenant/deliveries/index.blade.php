<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Operations board</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ auth()->user()->company?->settings?->operation_label_plural ?? 'Deliveries' }}</h1><p class="mt-1 text-sm text-slate-500">Create, assign, and monitor all current work.</p></div>
            <div class="flex flex-wrap gap-3"><a href="{{ route('tenant.operations.reports') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reports</a><a href="{{ route('tenant.deliveries.create') }}" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Create {{ auth()->user()->company?->settings?->operation_label_singular ?? 'delivery' }}</a></div>
        </div>
    </x-slot>

    @if ($errors->any())
        <section role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold">The requested operation could not be completed.</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>
    @endif

    <form method="GET" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1.5fr_1fr_1fr_auto]">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search reference, customer, or address" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="status" class="rounded-xl border-slate-300 text-sm"><option value="">All statuses</option>@foreach (\App\Models\Delivery::statuses() as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        <select name="personnel_id" class="rounded-xl border-slate-300 text-sm"><option value="">All personnel</option>@foreach ($personnel as $person)<option value="{{ $person->id }}" @selected((string) ($filters['personnel_id'] ?? '') === (string) $person->id)>{{ $person->full_name }}</option>@endforeach</select>
        <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Apply</button>
    </form>

    <form method="POST" action="{{ route('tenant.deliveries.bulk-assign') }}">
        @csrf
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($deliveries as $delivery)
                    <article class="flex gap-4 px-5 py-5 transition hover:bg-slate-50"><input type="checkbox" name="delivery_ids[]" value="{{ $delivery->id }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"><a href="{{ route('tenant.deliveries.show', $delivery) }}" class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-semibold text-slate-900">{{ $delivery->reference }} · {{ $delivery->customer->name }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ $delivery->pickup_address }} → {{ $delivery->dropoff_address }}</p></div><div class="text-right"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ \App\Models\Delivery::statuses()[$delivery->status] }}</span><p class="mt-2 text-xs text-slate-500">{{ $delivery->deliveryPersonnel?->full_name ?: 'Unassigned' }}</p></div></div></a></article>
                @empty
                    <div class="px-6 py-16 text-center"><p class="font-semibold text-slate-900">No operations match these filters</p><p class="mt-2 text-sm text-slate-500">Create a customer, then create your first delivery.</p><a href="{{ route('tenant.deliveries.create') }}" class="mt-5 inline-flex rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white">Create delivery</a></div>
                @endforelse
            </div>
            @if ($deliveries->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $deliveries->links() }}</div>@endif
        </section>
        @if ($deliveries->isNotEmpty())
            <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-end"><p class="mr-auto text-sm text-slate-500">Select up to 100 eligible deliveries to change their assignment.</p><select name="delivery_personnel_id" required class="rounded-xl border-slate-300 text-sm"><option value="">Assign to active personnel</option>@foreach ($personnel as $person)<option value="{{ $person->id }}">{{ $person->full_name }}</option>@endforeach</select><button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Bulk assign</button></div>
        @endif
    </form>
</x-app-layout>
