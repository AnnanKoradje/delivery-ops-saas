<x-app-layout>
    <x-slot name="header">
        <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Field work</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">My assigned work</h1><p class="mt-1 text-sm text-slate-500">Only deliveries assigned to you appear here.</p></div>
    </x-slot>

    @if ($errors->any())
        <section role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold">The delivery completion could not be recorded.</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>
    @endif

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($deliveries as $delivery)
            @php($nextStatuses = match ($delivery->status) {
                \App\Models\Delivery::STATUS_ASSIGNED => [\App\Models\Delivery::STATUS_PICKED_UP],
                \App\Models\Delivery::STATUS_PICKED_UP => [\App\Models\Delivery::STATUS_IN_TRANSIT],
                default => [],
            })
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-slate-900">{{ $delivery->reference }}</p><p class="mt-1 text-xs text-slate-500">{{ $delivery->customer->name }}</p></div><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $statuses[$delivery->status] }}</span></div>
                <div class="mt-5 border-t border-slate-100 pt-4 text-sm leading-6 text-slate-600"><p><span class="font-semibold text-slate-800">Pickup:</span> {{ $delivery->pickup_address }}</p><p class="mt-3"><span class="font-semibold text-slate-800">Drop-off:</span> {{ $delivery->dropoff_address }}</p></div>

                @if ($nextStatuses)
                    <form method="POST" action="{{ route('tenant.assigned-deliveries.status', $delivery) }}" class="mt-5 space-y-3">@csrf @method('PATCH')<select name="status" class="w-full rounded-xl border-slate-300">@foreach ($nextStatuses as $status)<option value="{{ $status }}">{{ $statuses[$status] }}</option>@endforeach</select><textarea name="notes" rows="2" placeholder="Update note (optional)" class="w-full rounded-xl border-slate-300"></textarea><button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white">Update status</button></form>
                @endif

                @if ($delivery->status === \App\Models\Delivery::STATUS_IN_TRANSIT && ! $delivery->proof)
                    <form method="POST" action="{{ route('tenant.assigned-deliveries.proof.store', $delivery) }}" enctype="multipart/form-data" x-data="proofCapture('{{ $delivery->id }}')" x-init="restore()" class="mt-5 border-t border-slate-100 pt-5">
                        @csrf
                        <h2 class="font-semibold text-slate-900">Proof of delivery</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Add at least one photo or a recipient signature. Proof files remain private to your delivery company.</p>
                        <div class="mt-4"><label class="text-xs font-semibold uppercase tracking-[.12em] text-slate-500">Recipient name</label><input name="recipient_name" x-model="recipientName" @input="persist()" value="{{ old('recipient_name') }}" maxlength="120" autocomplete="name" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="Optional recipient name"></div>
                        <div class="mt-4"><label class="text-xs font-semibold uppercase tracking-[.12em] text-slate-500">Delivery photos</label><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700"><p class="mt-1 text-xs text-slate-500">Up to three JPG, PNG, or WebP photos; 5 MB each.</p></div>
                        <div class="mt-4"><div class="flex items-center justify-between gap-3"><label class="text-xs font-semibold uppercase tracking-[.12em] text-slate-500">Recipient signature</label><button type="button" @click="clearSignature()" class="text-xs font-semibold text-slate-600">Clear</button></div><canvas x-ref="canvas" @pointerdown="start($event)" @pointermove="draw($event)" @pointerup="stop()" @pointerleave="stop()" class="mt-2 h-36 w-full touch-none rounded-xl border border-dashed border-slate-300 bg-slate-50"></canvas><input x-ref="signature" type="file" name="signature" accept="image/png" class="hidden"><p class="mt-1 text-xs text-slate-500">Draw with a finger or mouse. The signature is retained on this device until you submit or clear it.</p></div>
                        <div class="mt-4"><label class="text-xs font-semibold uppercase tracking-[.12em] text-slate-500">Completion note</label><textarea name="notes" x-model="notes" @input="persist()" rows="2" maxlength="500" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="Optional delivery note">{{ old('notes') }}</textarea></div>
                        <button class="mt-5 w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Submit proof and mark delivered</button>
                    </form>
                @endif
            </article>
        @empty
            <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><p class="font-semibold text-slate-900">No assigned deliveries</p><p class="mt-2 text-sm text-slate-500">New assignments will appear here when your dispatcher allocates them.</p></div>
        @endforelse
    </div>
    @if (session('status'))<p class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>@endif

    <script>
        function proofCapture(deliveryId) {
            return {
                recipientName: '', notes: '', drawing: false, lastPoint: null, key: `delivery-proof-draft-${deliveryId}`,
                restore() { const saved = localStorage.getItem(this.key); if (saved) { const data = JSON.parse(saved); this.recipientName = data.recipientName || ''; this.notes = data.notes || ''; if (data.signature) { this.paintImage(data.signature); } } },
                persist() { const canvas = this.$refs.canvas; localStorage.setItem(this.key, JSON.stringify({ recipientName: this.recipientName, notes: this.notes, signature: canvas.dataset.signed === 'true' ? canvas.toDataURL('image/png') : null })); },
                point(event) { const bounds = this.$refs.canvas.getBoundingClientRect(); return { x: (event.clientX - bounds.left) * (this.$refs.canvas.width / bounds.width), y: (event.clientY - bounds.top) * (this.$refs.canvas.height / bounds.height) }; },
                prepare() { const canvas = this.$refs.canvas; if (!canvas.width) { canvas.width = canvas.offsetWidth * devicePixelRatio; canvas.height = canvas.offsetHeight * devicePixelRatio; const context = canvas.getContext('2d'); context.scale(devicePixelRatio, devicePixelRatio); context.lineWidth = 2; context.lineCap = 'round'; context.strokeStyle = '#0f172a'; } },
                start(event) { this.prepare(); this.drawing = true; this.lastPoint = this.point(event); this.$refs.canvas.setPointerCapture(event.pointerId); },
                draw(event) { if (!this.drawing) return; const point = this.point(event); const context = this.$refs.canvas.getContext('2d'); context.beginPath(); context.moveTo(this.lastPoint.x, this.lastPoint.y); context.lineTo(point.x, point.y); context.stroke(); this.lastPoint = point; this.$refs.canvas.dataset.signed = 'true'; },
                stop() { if (!this.drawing) return; this.drawing = false; if (this.$refs.canvas.dataset.signed === 'true') { this.$refs.canvas.toBlob(blob => { const transfer = new DataTransfer(); transfer.items.add(new File([blob], 'recipient-signature.png', { type: 'image/png' })); this.$refs.signature.files = transfer.files; this.persist(); }, 'image/png'); } },
                clearSignature() { this.prepare(); this.$refs.canvas.getContext('2d').clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height); this.$refs.canvas.dataset.signed = 'false'; this.$refs.signature.value = ''; this.persist(); },
                paintImage(source) { this.prepare(); const image = new Image(); image.onload = () => { this.$refs.canvas.getContext('2d').drawImage(image, 0, 0, this.$refs.canvas.width, this.$refs.canvas.height); this.$refs.canvas.dataset.signed = 'true'; this.$refs.canvas.toBlob(blob => { const transfer = new DataTransfer(); transfer.items.add(new File([blob], 'recipient-signature.png', { type: 'image/png' })); this.$refs.signature.files = transfer.files; }, 'image/png'); }; image.src = source; }
            }
        }
    </script>
</x-app-layout>
