<div class="space-y-6">
    <x-flash :success="$successMessage" :error="$errorMessage" :from-session="false" />

    <section class="card" aria-labelledby="status-heading">
        <h2 id="status-heading" class="text-lg font-bold">وضعیت تأیید</h2>
        @if ($profile)
            <p class="mt-2 flex items-center gap-2 text-sm">
                <x-status-pill :status="$profile->verification_status" />
                @if ($profile->verified_at)
                    <span class="text-slate-600">تأیید در {{ \App\Support\Jalali::date($profile->verified_at) }}</span>
                @endif
            </p>
            @if ($profile->verification_status === \App\Enums\DriverVerificationStatus::Rejected && $profile->rejection_reason)
                <p class="mt-2 text-sm text-rose-800">دلیل رد: {{ $profile->rejection_reason }}. پس از اصلاح، اطلاعات را دوباره ذخیره کنید.</p>
            @endif
        @else
            <p class="mt-2 text-sm text-slate-700">هنوز اطلاعات خودرو ثبت نشده است.</p>
        @endif
    </section>

    <section class="card" aria-labelledby="vehicle-heading">
        <h2 id="vehicle-heading" class="text-lg font-bold">اطلاعات خودرو</h2>

        @if ($profile?->isApproved())
            <p class="mt-2 text-sm text-slate-600">اطلاعات خودروی تأییدشده فقط از طریق پشتیبانی قابل تغییر است.</p>
        @endif

        <form wire:submit="saveProfile" class="mt-4 grid gap-4 md:grid-cols-2" novalidate>
            <x-form-field label="نوع خودرو" for="vehicle_type_id" required>
                <select id="vehicle_type_id" wire:model="vehicle_type_id" class="form-input" required @disabled($profile?->isApproved())
                        @error('vehicle_type_id') aria-invalid="true" aria-describedby="vehicle_type_id-error" @enderror>
                    <option value="">انتخاب کنید</option>
                    @foreach ($vehicleTypes as $vehicleType)
                        <option value="{{ $vehicleType->id }}" wire:key="vt-{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field label="شماره پلاک" for="plate_number" required>
                <input id="plate_number" type="text" wire:model="plate_number" class="form-input" required @disabled($profile?->isApproved())
                       @error('plate_number') aria-invalid="true" aria-describedby="plate_number-error" @enderror>
            </x-form-field>

            <x-form-field label="مدل خودرو" for="vehicle_model">
                <input id="vehicle_model" type="text" wire:model="vehicle_model" class="form-input" @disabled($profile?->isApproved())
                       @error('vehicle_model') aria-invalid="true" aria-describedby="vehicle_model-error" @enderror>
            </x-form-field>

            <x-form-field label="ظرفیت (کیلوگرم)" for="capacity_kg">
                <input id="capacity_kg" type="text" inputmode="numeric" dir="ltr" wire:model="capacity_kg" class="form-input" @disabled($profile?->isApproved())
                       @error('capacity_kg') aria-invalid="true" aria-describedby="capacity_kg-error" @enderror>
            </x-form-field>

            <x-form-field label="کد ملی" for="national_code" hint="۱۰ رقم، اختیاری">
                <input id="national_code" type="text" inputmode="numeric" dir="ltr" wire:model="national_code" class="form-input" @disabled($profile?->isApproved())
                       aria-describedby="national_code-hint @error('national_code') national_code-error @enderror"
                       @error('national_code') aria-invalid="true" @enderror>
            </x-form-field>

            @unless ($profile?->isApproved())
                <div class="flex items-end md:col-span-2">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">ذخیره و ارسال برای بررسی</button>
                </div>
            @endunless
        </form>
    </section>

    <section class="card" aria-labelledby="documents-heading">
        <h2 id="documents-heading" class="text-lg font-bold">مدارک</h2>
        <p class="mt-1 text-sm text-slate-600">
            فرمت‌های مجاز: {{ implode('، ', $allowedMimes) }} · حداکثر حجم {{ \App\Support\Jalali::number(intdiv($maxUploadKb, 1024)) }} مگابایت.
            مدارک فقط برای شما و کارشناسان پشتیبانی قابل مشاهده است.
        </p>

        @if ($profile)
            <form wire:submit="uploadDocument" class="mt-4 grid gap-4 md:grid-cols-3" novalidate>
                <x-form-field label="نوع مدرک" for="document_type" required>
                    <select id="document_type" wire:model="document_type" class="form-input" required
                            @error('document_type') aria-invalid="true" aria-describedby="document_type-error" @enderror>
                        <option value="">انتخاب کنید</option>
                        @foreach ($documentTypes as $type)
                            <option value="{{ $type->value }}" wire:key="dt-{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field label="فایل مدرک" for="document" required>
                    <input id="document" type="file" wire:model="document" class="form-input" required
                           accept="{{ collect($allowedMimes)->map(fn ($mime) => '.'.$mime)->implode(',') }}"
                           @error('document') aria-invalid="true" aria-describedby="document-error" @enderror>
                    <p class="text-xs text-slate-500" wire:loading wire:target="document">در حال بارگذاری فایل...</p>
                </x-form-field>

                <div class="flex items-end">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="document,uploadDocument">بارگذاری مدرک</button>
                </div>
            </form>
        @else
            <p class="mt-4 text-sm text-slate-700">ابتدا اطلاعات خودرو را ثبت کنید، سپس مدارک را بارگذاری کنید.</p>
        @endif

        @if ($documents->isNotEmpty())
            <ul class="mt-6 divide-y divide-slate-100 text-sm">
                @foreach ($documents as $item)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="doc-{{ $item->id }}">
                        <span>
                            <strong>{{ $item->type->label() }}</strong>
                            <span class="text-slate-500">· {{ \App\Support\Jalali::date($item->created_at) }}</span>
                        </span>
                        <span class="flex items-center gap-3">
                            <x-status-pill :status="$item->status" />
                            <a href="{{ route('documents.show', $item) }}" class="link" target="_blank" rel="noopener">مشاهده فایل</a>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
