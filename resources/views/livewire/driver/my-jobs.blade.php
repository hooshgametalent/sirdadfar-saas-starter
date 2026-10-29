<div class="space-y-6">
    <x-flash :success="$successMessage" :error="$errorMessage" :from-session="false" />

    @forelse ($orders as $order)
        <article class="card" wire:key="job-{{ $order->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold">
                        {{ $order->originCity?->name }} به {{ $order->destinationCity?->name }}
                        <span class="text-sm font-normal text-slate-500" dir="ltr">{{ $order->reference }}</span>
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $order->cargo_type }} · {{ \App\Support\Jalali::number($order->weight_kg) }} کیلوگرم ·
                        بارگیری {{ \App\Support\Jalali::date($order->pickup_date, 'l j F Y') }} ·
                        کرایه {{ \App\Support\Jalali::formatToman($order->final_price) }}
                    </p>
                </div>
                <x-status-pill :status="$order->status" />
            </div>

            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-slate-500">صاحب بار</dt>
                    <dd class="mt-1">{{ $order->shipper?->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">شماره تماس صاحب بار</dt>
                    <dd class="mt-1" dir="ltr"><a class="link" href="tel:{{ $order->shipper?->mobile }}">{{ $order->shipper?->mobile }}</a></dd>
                </div>
                <div>
                    <dt class="text-slate-500">نشانی‌ها</dt>
                    <dd class="mt-1">مبدأ: {{ $order->origin_address }}<br>مقصد: {{ $order->destination_address }}</dd>
                </div>
            </dl>

            <div class="mt-4 flex flex-wrap gap-2">
                @if ($order->status === \App\Enums\OrderStatus::Assigned)
                    <button type="button" class="btn-primary" wire:click="advance({{ $order->id }}, 'loaded')" wire:loading.attr="disabled">بار بارگیری شد</button>
                @elseif ($order->status === \App\Enums\OrderStatus::Loaded)
                    <button type="button" class="btn-primary" wire:click="advance({{ $order->id }}, 'in_transit')" wire:loading.attr="disabled">حرکت به سمت مقصد</button>
                @elseif ($order->status === \App\Enums\OrderStatus::InTransit)
                    <button type="button" class="btn-primary" wire:click="advance({{ $order->id }}, 'delivered')" wire:confirm="تحویل بار به گیرنده تأیید شود؟" wire:loading.attr="disabled">بار تحویل شد</button>
                @endif
            </div>
        </article>
    @empty
        <p class="card text-sm text-slate-600">هنوز سفری به شما تخصیص داده نشده است.</p>
    @endforelse

    {{ $orders->links() }}
</div>
