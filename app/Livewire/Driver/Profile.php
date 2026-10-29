<?php

namespace App\Livewire\Driver;

use App\Enums\DocumentType;
use App\Enums\DriverVerificationStatus;
use App\Enums\ReviewStatus;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\VehicleType;
use App\Services\AuditLogger;
use App\Support\Jalali;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Vehicle information and verification documents of the signed in driver.
 * Documents go to the private disk under driver-documents/{profile_id}/
 * with generated file names and are downloaded only through
 * App\Http\Controllers\DriverDocumentController.
 */
#[Layout('layouts.panel')]
#[Title('پروفایل و مدارک راننده')]
class Profile extends Component
{
    use WithFileUploads;

    public string $vehicle_type_id = '';

    public string $plate_number = '';

    public string $vehicle_model = '';

    public string $capacity_kg = '';

    public string $national_code = '';

    public string $document_type = '';

    /**
     * @var TemporaryUploadedFile|null
     */
    public $document = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $profile = $this->profile();

        if ($profile !== null) {
            $this->vehicle_type_id = (string) $profile->vehicle_type_id;
            $this->plate_number = (string) $profile->plate_number;
            $this->vehicle_model = (string) $profile->vehicle_model;
            $this->capacity_kg = $profile->capacity_kg === null ? '' : (string) $profile->capacity_kg;
            $this->national_code = (string) $profile->national_code;
        }
    }

    public function saveProfile(): void
    {
        $this->resetMessages();

        $user = $this->user();
        $profile = $this->profile();

        if ($profile === null) {
            $this->authorize('create', DriverProfile::class);
        } else {
            $this->authorize('update', $profile);
        }

        if ($profile?->isApproved() === true) {
            $this->errorMessage = 'اطلاعات خودروی تأییدشده فقط از طریق پشتیبانی قابل تغییر است.';

            return;
        }

        $this->national_code = Jalali::toLatinDigits(trim($this->national_code));
        $this->capacity_kg = preg_replace('/[^\d]/', '', Jalali::toLatinDigits($this->capacity_kg)) ?? '';

        $data = $this->validate([
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'plate_number' => ['required', 'string', 'max:32'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'capacity_kg' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'national_code' => ['nullable', 'digits:10'],
        ]);

        $attributes = [
            'vehicle_type_id' => (int) $data['vehicle_type_id'],
            'plate_number' => trim($data['plate_number']),
            'vehicle_model' => filled($data['vehicle_model']) ? trim($data['vehicle_model']) : null,
            'capacity_kg' => filled($data['capacity_kg']) ? (int) $data['capacity_kg'] : null,
            'national_code' => filled($data['national_code']) ? $data['national_code'] : null,
            // A new or rejected profile goes (back) to the verification queue.
            'verification_status' => DriverVerificationStatus::Pending,
            'rejection_reason' => null,
        ];

        if ($profile === null) {
            $profile = DriverProfile::create(['user_id' => $user->getKey()] + $attributes);
        } else {
            $profile->update($attributes);
        }

        app(AuditLogger::class)->log('driver_profile.submitted', $profile, [], $user);

        $this->successMessage = 'اطلاعات خودرو ذخیره شد و برای بررسی به پشتیبانی ارسال شد.';
    }

    public function uploadDocument(): void
    {
        $this->resetMessages();

        $user = $this->user();
        $profile = $this->profile();

        if ($profile === null) {
            $this->errorMessage = 'ابتدا اطلاعات خودرو را ثبت کنید، سپس مدارک را بارگذاری کنید.';

            return;
        }

        $this->authorize('create', DriverDocument::class);

        $this->validate([
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document' => [
                'required',
                'file',
                'mimes:'.implode(',', config('freight.documents.allowed_mimes')),
                'max:'.(int) config('freight.documents.max_upload_kb'),
            ],
        ]);

        $file = $this->document;
        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $path = $file->storeAs(
            'driver-documents/'.$profile->getKey(),
            Str::uuid()->toString().'.'.$extension,
            ['disk' => config('freight.documents.disk')],
        );

        $document = DriverDocument::create([
            'driver_profile_id' => $profile->getKey(),
            'type' => DocumentType::from($this->document_type),
            'path' => $path,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
            'mime' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'status' => ReviewStatus::Pending,
        ]);

        app(AuditLogger::class)->log('driver_document.uploaded', $document, ['type' => $document->type->value], $user);

        $this->reset('document', 'document_type');
        $this->successMessage = 'مدرک بارگذاری شد و پس از بررسی پشتیبانی تأیید می‌شود.';
    }

    public function render(): View
    {
        $profile = $this->profile();

        return view('livewire.driver.profile', [
            'profile' => $profile,
            'documents' => $profile?->documents()->latest()->get() ?? collect(),
            'vehicleTypes' => VehicleType::query()->active()->get(['id', 'name', 'capacity_kg']),
            'documentTypes' => DocumentType::cases(),
            'allowedMimes' => config('freight.documents.allowed_mimes'),
            'maxUploadKb' => (int) config('freight.documents.max_upload_kb'),
        ]);
    }

    protected function profile(): ?DriverProfile
    {
        return $this->user()->driverProfile()->with('vehicleType')->first();
    }

    protected function resetMessages(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
