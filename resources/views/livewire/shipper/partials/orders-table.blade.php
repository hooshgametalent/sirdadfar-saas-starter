<div class="mt-4 overflow-x-auto">
    <table class="w-full min-w-[40rem] text-start text-sm">
        <thead class="border-b border-slate-200 text-slate-500">
            <tr>
                <th scope="col" class="py-2 text-start font-medium">کد سفارش</th>
                <th scope="col" class="py-2 text-start font-medium">مسیر</th>
                <th scope="col" class="py-2 text-start font-medium">نوع بار</th>
                <th scope="col" class="py-2 text-start font-medium">تاریخ بارگیری</th>
                <th scope="col" class="py-2 text-start font-medium">پیشنهادها</th>
                <th scope="col" class="py-2 text-start font-medium">وضعیت</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($orders as $order)
                <tr wire:key="order-{{ $order->id }}">
                    <td class="py-3">
                        <a href="{{ route('shipper.orders.show', $order) }}" class="link" dir="ltr">{{ $order->reference }}</a>
                    </td>
                    <td class="py-3">{{ $order->originCity?->name }} به {{ $order->destinationCity?->name }}</td>
                    <td class="py-3">{{ $order->cargo_type }} · {{ \App\Support\Jalali::number($order->weight_kg) }} کیلوگرم</td>
                    <td class="py-3">{{ \App\Support\Jalali::date($order->pickup_date) }}</td>
                    <td class="py-3">{{ \App\Support\Jalali::number($order->offers_count ?? 0) }}</td>
                    <td class="py-3"><x-status-pill :status="$order->status" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
