<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <x-form-field label="فیلتر وضعیت" for="status-filter" class="w-full sm:w-64">
            <select id="status-filter" wire:model.live="status" class="form-input">
                <option value="">همه وضعیت‌ها</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" wire:key="status-{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </x-form-field>
        <a href="{{ route('shipper.orders.create') }}" class="btn-primary">ثبت درخواست جدید</a>
    </div>

    <section class="card" aria-label="فهرست سفارش‌ها">
        @if ($orders->isEmpty())
            <p class="text-sm text-slate-600">سفارشی با این شرایط پیدا نشد.</p>
        @else
            @include('livewire.shipper.partials.orders-table', ['orders' => $orders])

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </section>
</div>
