<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Delivery detail</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $delivery->reference }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $delivery->customer->name }} · {{ \App\Models\Delivery::statuses()[$delivery->status] }}</p>
            </div>
            <a href="{{ route('tenant.deliveries.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">All operations</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <section role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold">The requested operational update could not be completed.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_.8fr]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Route</h2>
                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Pickup</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $delivery->pickup_address }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Drop-off</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $delivery->dropoff_address }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Schedule</dt><dd class="mt-2 text-sm text-slate-700">{{ $delivery->scheduled_at?->format('M j, Y g:i A') ?: 'Unscheduled' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400">Notes</dt><dd class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $delivery->notes ?: 'No notes' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Status history</h2>
                <ol class="mt-5 space-y-4">
                    @forelse ($delivery->statusHistory as $history)
                        <li class="border-l-2 border-blue-200 pl-4">
                            <p class="text-sm font-semibold text-slate-800">{{ \App\Models\Delivery::statuses()[$history->to_status] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $history->actor?->name ?: 'System' }} · {{ $history->created_at->format('M j, g:i A') }}</p>
                            @if ($history->notes)
                                <p class="mt-2 text-sm text-slate-600">{{ $history->notes }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No status events recorded.</li>
                    @endforelse
                </ol>
            </section>

            @if ($delivery->proof)
                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3"><h2 class="font-semibold text-emerald-950">Proof of delivery</h2><span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-emerald-700">Private</span></div>
                    <p class="mt-2 text-sm text-emerald-900">Submitted {{ $delivery->proof->submitted_at->format('M j, Y g:i A') }} by {{ $delivery->proof->submittedBy?->name ?: 'Delivery personnel' }}.</p>
                    @if ($delivery->proof->recipient_name)
                        <p class="mt-2 text-sm text-emerald-900">Recipient: {{ $delivery->proof->recipient_name }}</p>
                    @endif
                    @if ($delivery->proof->notes)
                        <p class="mt-2 text-sm leading-6 text-emerald-900">{{ $delivery->proof->notes }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($delivery->proof->files as $file)
                            <a href="{{ route('tenant.proof-files.show', $file) }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-300 bg-white px-3 py-2 text-xs font-semibold text-emerald-900">View {{ ucfirst($file->kind) }}</a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3"><h2 class="font-semibold">Operational exceptions</h2><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $delivery->exceptions->where('status', 'open')->count() }} open</span></div>
                <div class="mt-5 space-y-4">
                    @forelse ($delivery->exceptions as $exception)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ \App\Models\DeliveryException::types()[$exception->type] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ ucfirst($exception->status) }} · {{ $exception->created_at->format('M j, g:i A') }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $exception->description }}</p>
                            @if ($exception->status === 'open')
                                <form method="POST" action="{{ route('tenant.exceptions.resolve', $exception) }}" class="mt-4 flex gap-2">
                                    @csrf
                                    <input name="notes" placeholder="Resolution note (optional)" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm">
                                    <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Resolve</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">No operational exceptions have been recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Assignment</h2>
                <p class="mt-1 text-sm text-slate-500">Current: {{ $delivery->deliveryPersonnel?->full_name ?: 'Unassigned' }}</p>
                @if (in_array($delivery->status, [\App\Models\Delivery::STATUS_UNASSIGNED, \App\Models\Delivery::STATUS_ASSIGNED], true))
                    <form method="POST" action="{{ route('tenant.deliveries.assign', $delivery) }}" class="mt-5 space-y-3">
                        @csrf
                        <select name="delivery_personnel_id" required class="w-full rounded-xl border-slate-300">
                            @foreach ($personnel as $person)
                                <option value="{{ $person->id }}" @selected($delivery->delivery_personnel_id === $person->id)>{{ $person->full_name }}</option>
                            @endforeach
                        </select>
                        <button class="w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Assign personnel</button>
                    </form>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Update status</h2>
                <form method="POST" action="{{ route('tenant.deliveries.status', $delivery) }}" class="mt-5 space-y-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="w-full rounded-xl border-slate-300">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($delivery->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" rows="3" placeholder="Status note (optional)" class="w-full rounded-xl border-slate-300"></textarea>
                    <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white">Record status</button>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <h2 class="font-semibold text-emerald-950">Public delivery tracking</h2>
                <p class="mt-2 text-sm leading-6 text-emerald-900">Share a private customer-safe tracking link. Rotating it immediately invalidates the prior link.</p>
                @if (session('tracking_link'))
                    <div x-data="{ copied: false, copyLink() { if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(this.$refs.trackingLink.value).then(() => this.copied = true); return; } this.$refs.trackingLink.select(); document.execCommand('copy'); this.copied = true; } }" class="mt-4 rounded-xl border border-emerald-200 bg-white p-3"><label class="text-xs font-semibold uppercase tracking-[.12em] text-slate-500">New private link</label><input x-ref="trackingLink" readonly value="{{ session('tracking_link') }}" class="mt-2 w-full rounded-lg border-slate-300 bg-slate-50 text-xs text-slate-700"><button type="button" @click="copyLink()" class="mt-3 rounded-lg border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-900" x-text="copied ? 'Copied' : 'Copy link'">Copy link</button><p class="mt-2 text-xs text-slate-500">The raw link is displayed once and is not stored in recoverable form.</p></div>
                @endif
                @if ($delivery->trackingLink && $delivery->trackingLink->isActive())
                    <p class="mt-4 text-sm font-semibold text-emerald-900">A public link is active.</p>
                    <form method="POST" action="{{ route('tenant.deliveries.tracking-link.create', $delivery) }}" class="mt-3">@csrf<button class="rounded-xl border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900">Rotate link</button></form>
                    <form method="POST" action="{{ route('tenant.tracking-links.revoke', $delivery->trackingLink) }}" class="mt-3">@csrf<button class="text-sm font-semibold text-rose-700">Revoke link</button></form>
                @else
                    <form method="POST" action="{{ route('tenant.deliveries.tracking-link.create', $delivery) }}" class="mt-4">@csrf<button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Create tracking link</button></form>
                @endif
            </section>

            <section class="rounded-2xl border border-blue-200 bg-blue-50 p-6 shadow-sm">
                <h2 class="font-semibold text-blue-950">Email tracking update</h2>
                <p class="mt-2 text-sm leading-6 text-blue-900">A verified platform sender will deliver the tracking update. The company registration email is used as Reply-To.</p>
                @if ($delivery->customer->email && $delivery->customer->tracking_email_consent_at && ! $delivery->customer->tracking_email_opted_out_at)
                    <form method="POST" action="{{ route('tenant.deliveries.tracking-email.store', $delivery) }}" class="mt-4">@csrf<button class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white">Send tracking email</button></form>
                    <p class="mt-2 text-xs text-blue-800">Local preview mode is active until a verified platform sender is configured.</p>
                @else
                    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-900">Save a customer email address and their consent to transactional tracking emails before an update can be sent.</p>
                @endif
                @if ($delivery->trackingNotifications->isNotEmpty())
                    <div class="mt-5 border-t border-blue-200 pt-4"><p class="text-xs font-semibold uppercase tracking-[.12em] text-blue-900">Email delivery audit</p><ul class="mt-3 space-y-2">@foreach ($delivery->trackingNotifications as $notification)<li class="text-sm text-blue-950"><span class="font-semibold">{{ ucfirst($notification->status) }}</span> · {{ $notification->recipient_email }} · {{ $notification->created_at->format('M j, g:i A') }}</li>@endforeach</ul></div>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Public delivery window</h2>
                <p class="mt-1 text-sm text-slate-500">Optional estimate shown on the tracking link. It is not a live ETA.</p>
                <form method="POST" action="{{ route('tenant.deliveries.tracking-window.update', $delivery) }}" class="mt-4 space-y-3">
                    @csrf
                    <div><label class="text-xs font-semibold uppercase tracking-[.12em] text-slate-500">Starts</label><input type="datetime-local" name="delivery_window_starts_at" value="{{ old('delivery_window_starts_at', $delivery->delivery_window_starts_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></div>
                    <div><label class="text-xs font-semibold uppercase tracking-[.12em] text-slate-500">Ends</label><input type="datetime-local" name="delivery_window_ends_at" value="{{ old('delivery_window_ends_at', $delivery->delivery_window_ends_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></div>
                    <button class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">Save window</button>
                </form>
            </section>

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <h2 class="font-semibold text-amber-950">Report exception</h2>
                <form method="POST" action="{{ route('tenant.deliveries.exceptions.store', $delivery) }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="type" class="w-full rounded-xl border-amber-200 text-sm">@foreach (\App\Models\DeliveryException::types() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                    <textarea name="description" rows="3" required placeholder="What happened?" class="w-full rounded-xl border-amber-200 text-sm"></textarea>
                    <button class="w-full rounded-xl bg-amber-900 px-4 py-3 text-sm font-semibold text-white">Record exception</button>
                </form>
            </section>

            @if (in_array($delivery->status, [\App\Models\Delivery::STATUS_UNASSIGNED, \App\Models\Delivery::STATUS_ASSIGNED, \App\Models\Delivery::STATUS_PICKED_UP], true))
                <section class="rounded-2xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
                    <h2 class="font-semibold text-rose-950">Cancel delivery</h2>
                    <form method="POST" action="{{ route('tenant.deliveries.cancel', $delivery) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="notes" rows="3" required placeholder="Cancellation reason" class="w-full rounded-xl border-rose-200 text-sm"></textarea>
                        <button class="w-full rounded-xl bg-rose-700 px-4 py-3 text-sm font-semibold text-white">Cancel delivery</button>
                    </form>
                </section>
            @endif

            @if (session('status'))
                <p class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
            @endif
        </aside>
    </div>
</x-app-layout>
