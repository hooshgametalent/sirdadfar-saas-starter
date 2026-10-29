<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the first admin from ADMIN_MOBILE and ADMIN_PASSWORD. Nothing is
     * created when they are not set, so no credentials live in the code.
     */
    public function run(): void
    {
        $mobile = config('freight.admin.mobile');
        $password = config('freight.admin.password');

        if (blank($mobile) || blank($password)) {
            $this->command?->warn('ADMIN_MOBILE / ADMIN_PASSWORD are not set; skipping admin account creation.');

            return;
        }

        if (preg_match('/^09\d{9}$/', (string) $mobile) !== 1) {
            $this->command?->error('ADMIN_MOBILE must be an 11 digit mobile number starting with 09; skipping admin account creation.');

            return;
        }

        $admin = User::query()->firstOrNew(['mobile' => $mobile]);
        $admin->fill([
            'name' => config('freight.admin.name'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        if (! $admin->exists) {
            $admin->password = $password;
        }

        $admin->save();

        $this->command?->info('Admin account is ready for mobile '.$mobile.'.');
    }
}
