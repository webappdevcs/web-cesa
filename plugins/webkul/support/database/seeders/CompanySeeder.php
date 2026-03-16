<?php

namespace Webkul\Support\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Currency;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            if (
                ! Schema::hasTable('users')
                || ! Schema::hasTable('companies')
                || ! Schema::hasTable('partners_partners')
            ) {
                throw new Exception('Required tables are missing.');
            }

            DB::table('partners_partners')->delete();
            DB::table('companies')->delete();
            DB::table('users')->delete();

            $user = User::first();
            $companyName = Str::of(config('app.name', 'Company'))->squish()->value() ?: 'Company';
            $currencyCode = Str::upper((string) config('app.currency', 'IDR'));
            $companyCode = 'CMP-'.Str::upper(substr(sha1($companyName), 0, 8));
            $website = config('app.url');

            $partnerId = DB::table('partners_partners')->insertGetId([
                'sub_type'         => 'company',
                'company_registry' => null,
                'name'             => $companyName,
                'email'            => null,
                'website'          => $website,
                'tax_id'           => null,
                'phone'            => null,
                'mobile'           => null,
                'creator_id'       => $user?->id,
                'color'            => '#AAAAAA',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $currency = Currency::query()
                ->where('name', $currencyCode)
                ->first();

            if (! $currency) {
                throw new Exception("Currency '{$currencyCode}' not found.");
            }

            DB::table('companies')->insert([
                'sort'                => 1,
                'name'                => $companyName,
                'tax_id'              => null,
                'registration_number' => null,
                'company_id'          => $companyCode,
                'creator_id'          => $user?->id,
                'email'               => null,
                'phone'               => null,
                'mobile'              => null,
                'color'               => '#AAAAAA',
                'is_active'           => true,
                'founded_date'        => null,
                'currency_id'         => $currency->id,
                'website'             => $website,
                'partner_id'          => $partnerId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
        }
    }
}
