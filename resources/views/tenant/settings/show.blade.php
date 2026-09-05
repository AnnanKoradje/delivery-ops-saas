<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Tenant configuration</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Terminology preferences</h1>
        </div>
    </x-slot>

    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70">
        @if (session('status'))
            <p class="mb-6 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        @if (auth()->user()->can('update', $setting))
            <form method="POST" action="{{ route('tenant.settings.update', $setting) }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                @method('PATCH')
                <div><label class="text-sm font-semibold">Personnel singular</label><input name="personnel_label_singular" value="{{ old('personnel_label_singular', $setting->personnel_label_singular) }}" class="mt-2 w-full rounded-xl border-slate-300"></div>
                <div><label class="text-sm font-semibold">Personnel plural</label><input name="personnel_label_plural" value="{{ old('personnel_label_plural', $setting->personnel_label_plural) }}" class="mt-2 w-full rounded-xl border-slate-300"></div>
                <div><label class="text-sm font-semibold">Operation singular</label><input name="operation_label_singular" value="{{ old('operation_label_singular', $setting->operation_label_singular) }}" class="mt-2 w-full rounded-xl border-slate-300"></div>
                <div><label class="text-sm font-semibold">Operation plural</label><input name="operation_label_plural" value="{{ old('operation_label_plural', $setting->operation_label_plural) }}" class="mt-2 w-full rounded-xl border-slate-300"></div>
                <div><label class="text-sm font-semibold">Time zone</label><input name="time_zone" value="{{ old('time_zone', $setting->time_zone) }}" class="mt-2 w-full rounded-xl border-slate-300"></div>
                <label class="flex items-center gap-3 pt-8 text-sm font-semibold"><input type="checkbox" name="tracking_enabled" value="1" @checked(old('tracking_enabled', $setting->tracking_enabled)) class="rounded border-slate-300 text-blue-600">Enable public tracking when operations launch</label>
                <div class="sm:col-span-2 flex justify-end"><button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Save workspace settings</button></div>
            </form>
        @else
            <dl class="grid gap-6 sm:grid-cols-2">
                <div><dt class="text-sm font-medium text-slate-500">Personnel terminology</dt><dd class="mt-1 text-base font-semibold text-slate-950">{{ $setting->personnel_label_singular }} / {{ $setting->personnel_label_plural }}</dd></div>
                <div><dt class="text-sm font-medium text-slate-500">Operation terminology</dt><dd class="mt-1 text-base font-semibold text-slate-950">{{ $setting->operation_label_singular }} / {{ $setting->operation_label_plural }}</dd></div>
                <div><dt class="text-sm font-medium text-slate-500">Time zone</dt><dd class="mt-1 text-base font-semibold text-slate-950">{{ $setting->time_zone }}</dd></div>
                <div><dt class="text-sm font-medium text-slate-500">Public tracking</dt><dd class="mt-1 text-base font-semibold text-slate-950">{{ $setting->tracking_enabled ? 'Enabled' : 'Disabled' }}</dd></div>
            </dl>
        @endif
    </section>
</x-app-layout>
