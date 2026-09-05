<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('platform.companies.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">← Company applications</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $company->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $company->contact_email }} · {{ ucfirst($company->status) }}</p>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <section class="space-y-6">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Application details</h2>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Contact</dt>
                        <dd class="mt-1 font-medium">{{ $company->contact_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="mt-1 font-medium">{{ $company->contact_phone ?: 'Not supplied' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Business type</dt>
                        <dd class="mt-1 font-medium">{{ $company->business_type ?: 'Not supplied' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Submitted</dt>
                        <dd class="mt-1 font-medium">{{ $company->created_at->format('M j, Y H:i') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Address</dt>
                        <dd class="mt-1 font-medium">{{ $company->address ?: 'Not supplied' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Review history</h2>
                <ol class="mt-5 space-y-4">
                    @forelse ($company->reviewEvents as $event)
                        <li class="border-l-2 border-slate-200 pl-4">
                            <p class="text-sm font-semibold text-slate-800">{{ str_replace('_', ' ', ucfirst($event->event_type)) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $event->created_at->format('M j, Y H:i') }} · {{ $event->actor?->name ?? 'Public application' }}</p>
                            @if ($event->note)
                                <p class="mt-2 text-sm text-slate-600">{{ $event->note }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No review events recorded.</li>
                    @endforelse
                </ol>
            </article>
        </section>

        <aside class="space-y-6">
            @if (session('status'))
                <p class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
            @endif

            @if (session('admin_invitation_url'))
                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <p class="text-sm font-semibold text-blue-900">Company Admin invitation created</p>
                    <p class="mt-2 text-sm leading-6 text-blue-800">Copy this one-time link and deliver it securely to {{ $company->contact_email }}. It expires after seven days.</p>
                    <input readonly value="{{ session('admin_invitation_url') }}" class="mt-4 w-full rounded-lg border-blue-200 bg-white text-xs text-slate-700">
                </article>
            @endif

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Lifecycle controls</h2>

                @if ($company->status === 'pending')
                    <form method="POST" action="{{ route('platform.companies.approve', $company) }}" class="mt-5">
                        @csrf
                        <button class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">Approve and create Company Admin invitation</button>
                    </form>
                    <form method="POST" action="{{ route('platform.companies.reject', $company) }}" class="mt-3 space-y-3">
                        @csrf
                        <textarea name="note" rows="3" placeholder="Optional rejection note" class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                        <button class="w-full rounded-xl border border-rose-200 px-4 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50">Reject application</button>
                    </form>
                @elseif ($company->status === 'active')
                    <form method="POST" action="{{ route('platform.companies.suspend', $company) }}" class="mt-5 space-y-3">
                        @csrf
                        <textarea name="note" rows="3" placeholder="Optional suspension note" class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                        <button class="w-full rounded-xl border border-rose-200 px-4 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50">Suspend workspace</button>
                    </form>
                @elseif ($company->status === 'suspended')
                    <form method="POST" action="{{ route('platform.companies.reactivate', $company) }}" class="mt-5 space-y-3">
                        @csrf
                        <textarea name="note" rows="3" placeholder="Optional reactivation note" class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                        <button class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">Reactivate workspace</button>
                    </form>
                @else
                    <p class="mt-4 text-sm leading-6 text-slate-500">Rejected applications cannot be reactivated. A new application must be submitted.</p>
                @endif
            </article>
        </aside>
    </div>
</x-app-layout>
