<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $countryIso = (string)config('restoline.restaurant.country_iso', 'BE');
        $currencyCode = (string)config('restoline.restaurant.currency_code', 'EUR');

        if (Schema::hasTable('countries')) {
            $countryId = DB::table('countries')->where('iso_code_2', $countryIso)->value('country_id');
            if ($countryId) {
                DB::table('countries')->update(['is_default' => false]);
                DB::table('countries')->where('country_id', $countryId)->update([
                    'status' => true,
                    'is_default' => true,
                ]);
            }
        }

        if (Schema::hasTable('currencies')) {
            $currencyId = DB::table('currencies')->where('currency_code', $currencyCode)->value('currency_id');
            if ($currencyId) {
                DB::table('currencies')->update(['is_default' => false]);
                DB::table('currencies')->where('currency_id', $currencyId)->update([
                    'currency_status' => true,
                    'is_default' => true,
                    'decimal_sign' => ',',
                    'thousand_sign' => '.',
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};
