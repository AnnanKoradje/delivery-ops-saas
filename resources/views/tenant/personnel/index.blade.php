<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Company workspace</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ auth()->user()->company->settings?->personnel_label_plural ?? 'Delivery personnel' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Maintain operational personnel records scoped to this company only.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1fr_.82fr]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($personnel as $person)
                    <article class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $person->full_name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">
                                {{ $person->employee_code ?: 'No employee code' }} · {{ $person->phone ?: 'No phone supplied' }}
                                @if ($person->user)
                                    · Linked account
                                @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('tenant.personnel.status', $person) }}" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="rounded-lg border-slate-300 py-1.5 text-xs font-semibold">
                                <option value="active" @selected($person->status === 'active')>Active</option>
                                <option value="inactive" @selected($person->status === 'inactive')>Inactive</option>
                            </select>
                            <button class="text-xs font-semibold text-blue-700 hover:text-blue-900">Save</button>
                        </form>
                    </article>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">No delivery-personnel records have been added yet.</div>
                @endforelse
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Add {{ auth()->user()->company->settings?->personnel_label_singular ?? 'delivery person' }}</h2>
            <form method="POST" action="{{ route('tenant.personnel.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-semibold">Full name</label>
                    <input name="full_name" required class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    @error('full_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div><label class="text-sm font-semibold">Email <span class="font-normal text-slate-400">optional</span></label><input type="email" name="email" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="text-sm font-semibold">Phone <span class="font-normal text-slate-400">optional</span></label><input name="phone" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="text-sm font-semibold">Employee code <span class="font-normal text-slate-400">optional</span></label><input name="employee_code" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="text-sm font-semibold">Notes <span class="font-normal text-slate-400">optional</span></label><textarea name="notes" rows="3" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></textarea></div>
                <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">Add personnel record</button>
            </form>
            @if (session('status'))
                <p class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
            @endif
        </aside>
    </div>
</x-app-layout>
