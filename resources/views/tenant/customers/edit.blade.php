<x-app-layout>
    <x-slot name="header">
        <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Operations directory</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Edit customer</h1><p class="mt-1 text-sm text-slate-500">Update contact details for {{ $customer->name }}.</p></div>
    </x-slot>

    <form method="POST" action="{{ route('tenant.customers.update', $customer) }}" class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PATCH')
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="text-sm font-semibold">Name</label><input name="name" value="{{ old('name', $customer->name) }}" required class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">@error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-semibold">Email</label><input type="email" name="email" value="{{ old('email', $customer->email) }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">@error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-semibold">Phone</label><input name="phone" value="{{ old('phone', $customer->phone) }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"></div>
            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4"><input type="hidden" name="tracking_email_consent" value="0"><label class="flex cursor-pointer items-start gap-3"><input type="checkbox" name="tracking_email_consent" value="1" @checked(old('tracking_email_consent', (bool) $customer->tracking_email_consent_at && ! $customer->tracking_email_opted_out_at)) class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"><span><span class="block text-sm font-semibold text-slate-900">Customer consents to transactional tracking emails</span><span class="mt-1 block text-xs leading-5 text-slate-500">Use only for delivery updates and private tracking links. The verified platform sender delivers the email; this company’s registration email is used as Reply-To.</span></span></label></div>
            <div class="sm:col-span-2"><label class="text-sm font-semibold">Address</label><textarea name="address" rows="4" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('address', $customer->address) }}</textarea></div>
            <div class="sm:col-span-2"><label class="text-sm font-semibold">Notes</label><textarea name="notes" rows="3" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $customer->notes) }}</textarea></div>
        </div>
        <div class="mt-6 flex justify-end gap-3"><a href="{{ route('tenant.customers.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">Cancel</a><button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Save changes</button></div>
    </form>

    <section class="mt-6 max-w-2xl rounded-2xl border border-blue-100 bg-blue-50 p-6">
        <h2 class="font-semibold text-blue-950">Customer portal access</h2>
        <p class="mt-2 text-sm leading-6 text-blue-900">Issue a one-time, seven-day activation link to the customer’s saved email. Portal users can only view deliveries attached to this customer record.</p>
        @if ($errors->has('customer'))
            <p role="alert" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800">{{ $errors->first('customer') }}</p>
        @endif
        @if ($customer->portalAccount)
            <p class="mt-4 rounded-xl bg-white/80 p-3 text-sm font-semibold text-emerald-700">Portal account active for {{ $customer->portalAccount->email }}.</p>
        @else
            <form method="POST" action="{{ route('tenant.customers.portal-invitation', $customer) }}" class="mt-4">@csrf<button class="rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white">Create activation link</button></form>
        @endif
        @if (session('portal_invitation_link'))
            <div class="mt-4 rounded-xl bg-white p-4"><p class="text-sm font-semibold text-slate-900">One-time customer activation link</p><p class="mt-2 break-all text-sm text-blue-700">{{ session('portal_invitation_link') }}</p></div>
        @endif
    </section>
</x-app-layout>
