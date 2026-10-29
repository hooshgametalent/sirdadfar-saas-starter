<div>
    <p class="mb-6 text-sm text-slate-600">اطلاعات بار را وارد کنید تا پس از بررسی اپراتور برای رانندگان واجد شرایط ارسال شود.</p>

    <form wire:submit="submit" class="space-y-6" novalidate>
        <fieldset class="card space-y-4">
            <legend class="sr-only">مسیر حمل</legend>
            <div>
                <h2 class="text-lg font-bold">مسیر حمل</h2>
                <p class="text-sm text-slate-600">مبدأ و مقصد بار را وارد کنید.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-form-field label="استان مبدأ" for="origin_province_id" required>
                    <select id="origin_province_id" wire:model.live="origin_province_id" class="form-input" required
                            @error('origin_province_id') aria-invalid="true" aria-describedby="origin_province_id-error" @enderror>
                        <option value="">انتخاب کنید</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" wire:key="op-{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field label="شهر مبدأ" for="origin_city_id" required>
                    <select id="origin_city_id" wire:model="origin_city_id" class="form-input" required @disabled($originCities->isEmpty())
                            @error('origin_city_id') aria-invalid="true" aria-describedby="origin_city_id-error" @enderror>
                        <option value="">انتخاب کنید</option>
                        @foreach ($originCities as $city)
                            <option value="{{ $city->id }}" wire:key="oc-{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field label="نشانی مبدأ" for="origin_address" class="md:col-span-2" required>
                    <input id="origin_address" type="text" wire:model="origin_address" class="form-input" required
                           @error('origin_address') aria-invalid="true" aria-describedby="origin_address-error" @enderror>
                </x-form-field>

                <x-form-field label="استان مقصد" for="destination_province_id" required>
                    <select id="destination_province_id" wire:model.live="destination_province_id" class="form-input" required
                            @error('destination_province_id') aria-invalid="true" aria-describedby="destination_province_id-error" @enderror>
                        <option value="">انتخاب کنید</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" wire:key="dp-{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field label="شهر مقصد" for="destination_city_id" required>
                    <select id="destination_city_id" wire:model="destination_city_id" class="form-input" required @disabled($destinationCities->isEmpty())
                            @error('destination_city_id') aria-invalid="true" aria-describedby="destination_city_id-error" @enderror>
                        <option value="">انتخاب کنید</option>
                        @foreach ($destinationCities as $city)
                            <option value="{{ $city->id }}" wire:key="dc-{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field label="نشانی مقصد" for="destination_address" class="md:col-span-2" required>
                    <input id="destination_address" type="text" wire:model="destination_address" class="form-input" required
                           @error('destination_address') aria-invalid="true" aria-describedby="destination_address-error" @enderror>
                </x-form-field>
            </div>
        </fieldset>

        <fieldset class="card space-y-4">
            <legend class="sr-only">مشخصات بار</legend>
            <div>
                <h2 class="text-lg font-bold">مشخصات بار</h2>
                <p class="text-sm text-slate-600">این اطلاعات به انتخاب راننده و برآورد قیمت کمک می‌کند.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <x-form-field label="نوع بار" for="cargo_type" required>
                    <input id="cargo_type" type="text" wire:model="cargo_type" class="form-input" placeholder="مثلاً مصالح ساختمانی" required
                           @error('cargo_type') aria-invalid="true" aria-describedby="cargo_type-error" @enderror>
                </x-form-field>

                <x-form-field label="وزن تقریبی (کیلوگرم)" for="weight_kg" required>
                    <input id="weight_kg" type="text" inputmode="numeric" dir="ltr" wire:model="weight_kg" class="form-input" placeholder="8000" required
                           @error('weight_kg') aria-invalid="true" aria-describedby="weight_kg-error" @enderror>
                </x-form-field>

                <x-form-field label="حجم (متر مکعب)" for="volume_m3">
                    <input id="volume_m3" type="text" inputmode="decimal" dir="ltr" wire:model="volume_m3" class="form-input"
                           @error('volume_m3') aria-invalid="true" aria-describedby="volume_m3-error" @enderror>
                </x-form-field>

                <x-form-field label="نوع خودرو" for="vehicle_type_id" required>
                    <select id="vehicle_type_id" wire:model="vehicle_type_id" class="form-input" required
                            @error('vehicle_type_id') aria-invalid="true" aria-describedby="vehicle_type_id-error" @enderror>
                        <option value="">انتخاب کنید</option>
                        @foreach ($vehicleTypes as $vehicleType)
                            <option value="{{ $vehicleType->id }}" wire:key="vt-{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field label="تاریخ بارگیری" for="pickup_date" required>
                    <input id="pickup_date" type="date" dir="ltr" wire:model.live="pickup_date" class="form-input" min="{{ now()->toDateString() }}" required
                           aria-describedby="pickup_date-jalali @error('pickup_date') pickup_date-error @enderror"
                           @error('pickup_date') aria-invalid="true" @enderror>
                    <p id="pickup_date-jalali" class="text-xs text-slate-600" aria-live="polite">
                        @if ($pickupDateJalali)
                            معادل شمسی: {{ $pickupDateJalali }}
                        @else
                            تاریخ را انتخاب کنید تا معادل شمسی نمایش داده شود.
                        @endif
                    </p>
                </x-form-field>

                <div class="grid grid-cols-2 gap-2">
                    <x-form-field label="از ساعت" for="pickup_window_start">
                        <input id="pickup_window_start" type="time" dir="ltr" wire:model="pickup_window_start" class="form-input"
                               @error('pickup_window_start') aria-invalid="true" aria-describedby="pickup_window_start-error" @enderror>
                    </x-form-field>
                    <x-form-field label="تا ساعت" for="pickup_window_end">
                        <input id="pickup_window_end" type="time" dir="ltr" wire:model="pickup_window_end" class="form-input"
                               @error('pickup_window_end') aria-invalid="true" aria-describedby="pickup_window_end-error" @enderror>
                    </x-form-field>
                </div>
            </div>

            <fieldset>
                <legend class="text-sm font-medium text-slate-800">شرایط ویژه</legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($requirements as $key => $label)
                        <label class="flex items-center gap-2 text-sm" wire:key="req-{{ $key }}">
                            <input type="checkbox" value="{{ $key }}" wire:model="special_requirements" class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('special_requirements.*')
                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </fieldset>
        </fieldset>

        <fieldset class="card space-y-4">
            <legend class="sr-only">قیمت و توضیحات</legend>
            <h2 class="text-lg font-bold">قیمت و توضیحات</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <x-form-field label="قیمت پیشنهادی (تومان)" for="offered_price" hint="رانندگان می‌توانند قیمت متفاوتی پیشنهاد دهند." required>
                    <input id="offered_price" type="text" inputmode="numeric" dir="ltr" wire:model="offered_price" class="form-input" placeholder="28500000" required
                           aria-describedby="offered_price-hint @error('offered_price') offered_price-error @enderror"
                           @error('offered_price') aria-invalid="true" @enderror>
                </x-form-field>

                <x-form-field label="توضیحات و شرایط ویژه" for="notes" class="md:col-span-2">
                    <textarea id="notes" rows="3" wire:model="notes" class="form-input" placeholder="نیاز به بارگیری، بیمه، بار حساس یا هر توضیح ضروری دیگر"
                              @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror></textarea>
                </x-form-field>
            </div>
        </fieldset>

        <div class="flex justify-end gap-3">
            <a href="{{ route('shipper.orders.index') }}" class="btn-secondary">انصراف</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="submit">ثبت و ارسال برای بررسی</button>
        </div>
    </form>
</div>
