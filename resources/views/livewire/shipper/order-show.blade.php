<div class="space-y-6">
    <x-flash :success="$successMessage" :error="$errorMessage" :from-session="false" />

    <section class="card" aria-labelledby="order-summary-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="order-summary-heading" class="text-lg font-bold">
                سفارش <span dir="ltr">{{ $details->reference }}</span>
            </h2>
            <x-status-pill :status="$details->status" />
        </div>

        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-slate-500">مبدأ</dt>
                <dd class="mt-1">{{ $details->originCity?->province?->name }}، {{ $details->originCity?->name }}، {{ $details->origin_address }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">مقصد</dt>
                <dd class="mt-1">{{ $details->destinationCity?->province?->name }}، {{ $details->destinationCity?->name }}، {{ $details->destination_address }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">نوع بار و وزن</dt>
                <dd class="mt-1">{{ $details->cargo_type }} · {{ \App\Support\Jalali::number($details->weight_kg) }} کیلوگرم</dd>
            </div>
            <div>
                <dt class="text-slate-500">نوع خودرو</dt>
                <dd class="mt-1">{{ $details->vehicleType?->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">تاریخ بارگیری</dt>
                <dd class="mt-1">
                    {{ \App\Support\Jalali::date($details->pickup_date, 'l j F Y') }}
                    @if ($details->pickup_window_start)
                        · ساعت {{ \App\Support\Jalali::toPersianDigits(substr($details->pickup_window_start, 0, 5)) }}
                        @if ($details->pickup_window_end)
                            تا {{ \App\Support\Jalali::toPersianDigits(substr($details->pickup_window_end, 0, 5)) }}
                        @endif
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">قیمت پیشنهادی شما</dt>
                <dd class="mt-1">{{ \App\Support\Jalali::formatToman($details->offered_price) }}</dd>
            </div>
            @if ($details->final_price)
                <div>
                    <dt class="text-slate-500">قیمت نهایی</dt>
                    <dd class="mt-1 font-bold">{{ \App\Support\Jalali::formatToman($details->final_price) }}</dd>
                </div>
            @endif
            @if (! empty($details->special_requirements))
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">شرایط ویژه</dt>
                    <dd class="mt-1">
                        {{ collect(array_keys(array_filter($details->special_requirements)))->map(fn ($key) => $requirements[$key] ?? $key)->implode('، ') }}
                    </dd>
                </div>
            @endif
            @if ($details->notes)
                <div class="sm:col-span-full">
                    <dt class="text-slate-500">توضیحات</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $details->notes }}</dd>
                </div>
            @endif
            @if ($details->cancelled_reason)
                <div class="sm:col-span-full">
                    <dt class="text-slate-500">دلیل لغو</dt>
                    <dd class="mt-1">{{ $details->cancelled_reason }}</dd>
                </div>
            @endif
        </dl>
    </section>

    @if ($details->assignedDriver)
        <section class="card" aria-labelledby="driver-heading">
            <h2 id="driver-heading" class="text-lg font-bold">راننده تخصیص یافته</h2>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-slate-500">نام راننده</dt>
                    <dd class="mt-1">{{ $details->assignedDriver->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">شماره تماس</dt>
                    <dd class="mt-1" dir="ltr"><a class="link" href="tel:{{ $details->assignedDriver->mobile }}">{{ $details->assignedDriver->mobile }}</a></dd>
                </div>
                <div>
                    <dt class="text-slate-500">خودرو و پلاک</dt>
                    <dd class="mt-1">{{ $details->assignedDriver->driverProfile?->vehicleType?->name }} · {{ $details->assignedDriver->driverProfile?->plate_number }}</dd>
                </div>
            </dl>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2" aria-labelledby="offers-heading">
            <h2 id="offers-heading" class="text-lg font-bold">پیشنهادهای رانندگان</h2>

            @if ($offers->isEmpty())
                <p class="mt-4 text-sm text-slate-600">
                    @if ($details->status === \App\Enums\OrderStatus::PendingReview)
                        سفارش شما در انتظار بررسی اپراتور است. پس از تأیید، رانندگان می‌توانند پیشنهاد قیمت بدهند.
                    @else
                        هنوز پیشنهادی ثبت نشده است.
                    @endif
                </p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($offers as $offer)
                        @php($rating = $ratings->get($offer->driver_id))
                        <li class="flex flex-wrap items-center justify-between gap-3 py-4" wire:key="offer-{{ $offer->id }}">
                            <div class="text-sm">
                                <p class="font-bold">{{ \App\Support\Jalali::formatToman($offer->price) }}</p>
                                <p class="mt-1 text-slate-700">
                                    {{ $offer->driver?->name }}
                                    @if ($offer->driver?->driverProfile?->vehicleType)
                                        · {{ $offer->driver->driverProfile->vehicleType->name }}
                                    @endif
                                </p>
                                <p class="mt-1 text-slate-500">
                                    @if ($rating)
                                        امتیاز {{ \App\Support\Jalali::toPersianDigits(number_format((float) $rating->average, 1)) }} از ۵
                                        (بر اساس {{ \App\Support\Jalali::number((int) $rating->total) }} نظر)
                                    @else
                                        هنوز امتیازی ثبت نشده است.
                                    @endif
                                </p>
                                @if ($offer->message)
                                    <p class="mt-1 text-slate-600">{{ $offer->message }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <x-status-pill :status="$offer->status" />
                                @if ($canAccept && $offer->status === \App\Enums\OfferStatus::Pending)
                                    <button type="button" class="btn-primary"
                                            wire:click="acceptOffer({{ $offer->id }})"
                                            wire:confirm="این پیشنهاد پذیرفته شود؟ سایر پیشنهادها رد خواهند شد."
                                            wire:loading.attr="disabled">
                                        پذیرش پیشنهاد
                                    </button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card" aria-labelledby="history-heading">
            <h2 id="history-heading" class="text-lg font-bold">تاریخچه وضعیت</h2>
            <ol class="mt-4 space-y-4 border-s border-slate-200 ps-4">
                @foreach ($details->statusHistories as $history)
                    <li class="text-sm" wire:key="history-{{ $history->id }}">
                        <x-status-pill :status="$history->to_status" />
                        <p class="mt-1 text-slate-500">{{ \App\Support\Jalali::dateTime($history->created_at) }}</p>
                        @if ($history->note)
                            <p class="mt-1 text-slate-700">{{ $history->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    @if ($canReview)
        <section class="card" aria-labelledby="review-heading">
            <h2 id="review-heading" class="text-lg font-bold">ثبت نظر درباره این حمل</h2>
            <p class="mt-1 text-sm text-slate-600">نظر شما پس از بررسی پشتیبانی و فقط با رضایت شما در سایت نمایش داده می‌شود.</p>

            <form wire:submit="submitReview" class="mt-4 space-y-4" novalidate>
                <fieldset>
                    <legend class="text-sm font-medium text-slate-800">امتیاز شما</legend>
                    <div class="mt-2 flex gap-3">
                        @foreach ([1, 2, 3, 4, 5] as $score)
                            <label class="flex items-center gap-1 text-sm" wire:key="score-{{ $score }}">
                                <input type="radio" name="rating" value="{{ $score }}" wire:model="rating" class="text-teal-700 focus:ring-teal-600">
                                {{ \App\Support\Jalali::toPersianDigits($score) }}
                            </label>
                        @endforeach
                    </div>
                    @error('rating')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-form-field label="متن نظر" for="reviewBody" required>
                    <textarea id="reviewBody" rows="3" wire:model="reviewBody" class="form-input" required
                              @error('reviewBody') aria-invalid="true" aria-describedby="reviewBody-error" @enderror></textarea>
                </x-form-field>

                <x-form-field label="نام نمایشی" for="displayName" hint="این نام در صورت انتشار نمایش داده می‌شود. شماره موبایل هرگز نمایش داده نمی‌شود.">
                    <input id="displayName" type="text" wire:model="displayName" class="form-input"
                           aria-describedby="displayName-hint @error('displayName') displayName-error @enderror"
                           @error('displayName') aria-invalid="true" @enderror>
                </x-form-field>

                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="publishConsent" class="mt-1 size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                    <span>موافقم نظرم با نام نمایشی بالا در بخش نظرات مشتریان سایت منتشر شود.</span>
                </label>

                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="submitReview">ثبت نظر</button>
            </form>
        </section>
    @elseif ($details->review)
        <section class="card" aria-labelledby="my-review-heading">
            <h2 id="my-review-heading" class="text-lg font-bold">نظر شما</h2>
            <p class="mt-2 text-sm">امتیاز {{ \App\Support\Jalali::toPersianDigits($details->review->rating) }} از ۵ · <x-status-pill :status="$details->review->status" /></p>
            <p class="mt-2 text-sm text-slate-700">{{ $details->review->body }}</p>
        </section>
    @endif

    @if ($canCancel)
        <section class="card" aria-labelledby="cancel-heading">
            <h2 id="cancel-heading" class="text-lg font-bold">لغو سفارش</h2>
            <p class="mt-1 text-sm text-slate-600">تا پیش از تخصیص راننده می‌توانید سفارش را لغو کنید.</p>
            <form wire:submit="cancel" wire:confirm="سفارش لغو شود؟" class="mt-4 flex flex-wrap items-end gap-3" novalidate>
                <x-form-field label="دلیل لغو" for="cancelReason" class="min-w-64 flex-1" required>
                    <input id="cancelReason" type="text" wire:model="cancelReason" class="form-input" required
                           @error('cancelReason') aria-invalid="true" aria-describedby="cancelReason-error" @enderror>
                </x-form-field>
                <button type="submit" class="btn-danger" wire:loading.attr="disabled" wire:target="cancel">لغو سفارش</button>
            </form>
        </section>
    @endif
</div>
