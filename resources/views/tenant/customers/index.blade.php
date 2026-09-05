<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Operations directory</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Customers</h1>
            <p class="mt-1 text-sm text-slate-500">Maintain the contacts that receive your company’s deliveries.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1fr_.85fr]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><h2 class="font-semibold">Customer directory</h2><span class="text-xs font-medium text-slate-500">{{ $customers->total() }} total</span></div>
            <div class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <article class="px-6 py-4">
                        <div class="flex items-center justify-between gap-4"><p class="text-sm font-semibold text-slate-900">{{ $customer->name }}</p><a href="{{ route('tenant.customers.edit', $customer) }}" class="text-xs font-semibold text-blue-700 hover:text-blue-900">Edit</a></div>
                        <p class="mt-1 text-xs text-slate-500">{{ $customer->email ?: 'No email' }} · {{ $customer->phone ?: 'No phone' }}</p>
                        @if ($customer->address)<p class="mt-2 text-xs leading-5 text-slate-500">{{ $customer->address }}</p>@endif
                    </article>
                @empty
                    <div class="px-6 py-14 text-center text-sm text-slate-500">Add a customer before creating your first delivery.</div>
                @endforelse
            </div>
            @if ($customers->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $customers->links() }}</div>@endif
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Add customer</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Customer contacts stay inside this company workspace.</p>
            <form method="POST" action="{{ route('tenant.customers.store') }}" class="mt-5 space-y-4">
                @csrf
                <div><label class="text-sm font-semibold">Name</label><input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">@error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-semibold">Email <span class="font-normal text-slate-400">optional</span></label><input type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="text-sm font-semibold">Phone <span class="font-normal text-slate-400">optional</span></label><input name="phone" value="{{ old('phone') }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="text-sm font-semibold">Address <span class="font-normal text-slate-400">optional</span></label><textarea name="address" rows="3" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea></div>
                <div><label class="text-sm font-semibold">Notes <span class="font-normal text-slate-400">optional</span></label><textarea name="notes" rows="2" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea></div>
                <button class="w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Save customer</button>
            </form>
            @if (session('status'))<p class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>@endif
        </aside>
    </div>
</x-app-layout>
