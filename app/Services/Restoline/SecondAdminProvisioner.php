<?php

declare(strict_types=1);

namespace App\Services\Restoline;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the second super-admin once. The password is random and discarded.
 */
final class SecondAdminProvisioner
{
    public const string EMAIL = 'wokdasie@gmail.com';

    public function provision(): void
    {
        if (!Schema::hasTable('admin_users')) {
            return;
        }
        if (DB::table('admin_users')->where('email', self::EMAIL)->exists()) {
            return;
        }

        $existing = DB::table('admin_users')->where('super_user', 1)->orderBy('user_id')->first();
        $username = $this->username();
        $hashed = Hash::make(bin2hex(random_bytes(32)));
        $now = now();

        DB::table('admin_users')->insert([
            'name' => 'Wokdasie',
            'email' => self::EMAIL,
            'telephone' => null,
            'user_role_id' => $existing->user_role_id ?? null,
            'language_id' => $existing->language_id ?? null,
            'status' => 1,
            'sale_permission' => $existing->sale_permission ?? 1,
            'username' => $username,
            'password' => $hashed,
            'super_user' => 1,
            'is_activated' => 1,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function username(): string
    {
        $base = 'wokdasie';
        $candidate = $base;
        $suffix = 2;
        while (DB::table('admin_users')->where('username', $candidate)->exists()) {
            $candidate = $base.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
