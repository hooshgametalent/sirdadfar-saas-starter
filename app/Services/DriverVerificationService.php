<?php

namespace App\Services;

use App\Contracts\SmsSender;
use App\Enums\DriverVerificationStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\FreightException;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Approves or rejects driver profiles and documents. Used by the admin panel.
 */
class DriverVerificationService
{
    public function __construct(
        protected AuditLogger $audit,
        protected SmsSender $sms,
    ) {
        //
    }

    public function approve(DriverProfile $profile, User $staff): DriverProfile
    {
        $this->ensureStaff($staff);

        DB::transaction(function () use ($profile, $staff) {
            $profile->forceFill([
                'verification_status' => DriverVerificationStatus::Approved,
                'verified_at' => now(),
                'verified_by' => $staff->getKey(),
                'rejection_reason' => null,
            ])->save();

            $this->audit->log('driver.approved', $profile, ['user_id' => $profile->user_id], $staff);
        });

        $this->notify($profile, 'حساب راننده شما در بارراه تأیید شد و می‌توانید برای بارها پیشنهاد ثبت کنید.');

        return $profile;
    }

    public function reject(DriverProfile $profile, User $staff, string $reason): DriverProfile
    {
        $this->ensureStaff($staff);

        if (trim($reason) === '') {
            throw FreightException::because('ثبت دلیل رد مدارک الزامی است.');
        }

        DB::transaction(function () use ($profile, $staff, $reason) {
            $profile->forceFill([
                'verification_status' => DriverVerificationStatus::Rejected,
                'verified_at' => null,
                'verified_by' => $staff->getKey(),
                'rejection_reason' => trim($reason),
            ])->save();

            $this->audit->log('driver.rejected', $profile, ['user_id' => $profile->user_id, 'reason' => trim($reason)], $staff);
        });

        $this->notify($profile, 'مدارک راننده شما در بارراه تأیید نشد. دلیل: '.trim($reason));

        return $profile;
    }

    public function reviewDocument(DriverDocument $document, User $staff, ReviewStatus $status): DriverDocument
    {
        $this->ensureStaff($staff);

        $document->forceFill([
            'status' => $status,
            'reviewed_by' => $staff->getKey(),
            'reviewed_at' => now(),
        ])->save();

        $this->audit->log('driver_document.'.$status->value, $document, ['driver_profile_id' => $document->driver_profile_id], $staff);

        return $document;
    }

    protected function ensureStaff(User $staff): void
    {
        if (! $staff->is_active || ! $staff->isStaff()) {
            throw new AuthorizationException('فقط اپراتور یا مدیر می‌تواند مدارک رانندگان را بررسی کند.');
        }
    }

    protected function notify(DriverProfile $profile, string $message): void
    {
        $mobile = $profile->user?->mobile;

        if (filled($mobile)) {
            $this->sms->send($mobile, $message);
        }
    }
}
