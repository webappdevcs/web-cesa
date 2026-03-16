<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $seedData = new EmployeeSeedData;
        $creatorId = User::query()->value('id');
        $currencyCode = Str::upper((string) config('app.currency', 'IDR'));
        $currencyId = Currency::query()
            ->where('name', $currencyCode)
            ->value('id')
            ?? Currency::query()
                ->where('name', 'IDR')
                ->value('id')
            ?? Currency::query()->orderBy('id')->value('id');

        DB::transaction(function () use ($seedData, $creatorId, $currencyId): void {
            $seedData->companies()->each(function (array $companyData) use ($creatorId, $currencyId): void {
                $company = Company::query()
                    ->withTrashed()
                    ->firstOrNew(['name' => $companyData['name']]);

                $company->fill([
                    'creator_id'  => $creatorId,
                    'currency_id' => $currencyId,
                    'company_id'  => $companyData['company_id'],
                    'color'       => $companyData['color'],
                    'is_active'   => true,
                ]);

                $company->deleted_at = null;
                $company->save();
            });
        });
    }
}
