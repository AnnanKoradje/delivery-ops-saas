<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Company workspace</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Staff access</h1>
            <p class="mt-1 text-sm text-slate-500">Invite internal team members and manage their active workspace access.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1fr_.9fr]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-semibold">Current staff</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse ($staff as $member)
                    <article class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $member->name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $member->email }} · {{ $member->roles->pluck('name')->join(', ') ?: 'No role' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
                            @if ($member->id !== auth()->id())
                                <form method="POST" action="{{ $member->is_active ? route('tenant.staff.deactivate', $member) : route('tenant.staff.activate', $member) }}">
                                    @csrf
                                    <button class="text-xs font-semibold text-blue-700 hover:text-blue-900">{{ $member->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">No staff accounts have been activated yet.</div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold">Invite staff member</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Staff receive a one-time activation link valid for seven days.</p>
                <form method="POST" action="{{ route('tenant.staff.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold">Email</label>
                        <input type="email" name="email" required class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold">Workspace role</label>
                        <select name="role_slug" required class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($roles as $role)<option value="{{ $role->slug }}">{{ $role->name }}</option>@endforeach
                        </select>
                    </div>
                    <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">Create invitation</button>
                </form>
            </section>

            @if (session('staff_invitation_url'))
                <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <p class="text-sm font-semibold text-blue-900">One-time staff activation link</p>
                    <p class="mt-2 text-sm leading-6 text-blue-800">Copy this link and provide it securely to the invited staff member.</p>
                    <input readonly value="{{ session('staff_invitation_url') }}" class="mt-4 w-full rounded-lg border-blue-200 bg-white text-xs text-slate-700">
                </section>
            @endif

            @if (session('status'))
                <p class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
            @endif
        </aside>
    </div>
</x-app-layout>
