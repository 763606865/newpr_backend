<?php

namespace App\Services;

use App\Enums\CompanyProfileStatus;
use App\Models\Company;
use App\Models\Rc\CompanyProfile;
use Illuminate\Support\Arr;

class CompanyProfileService extends Service
{
    public function ensureForCompany(Company $company): CompanyProfile
    {
        return CompanyProfile::query()->firstOrCreate(
            ['company_id' => $company->id],
            ['profile_status' => CompanyProfileStatus::Draft],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CompanyProfile $profile, array $data): CompanyProfile
    {
        $profile->fill(Arr::only($data, [
            'short_name',
            'logo',
            'city_code',
            'scale_type',
            'nature_type',
            'industry_codes',
            'founded_at',
            'website',
            'introduction',
            'benefit_tags',
            'funding_stage',
        ]));

        if ($profile->profile_status === CompanyProfileStatus::Draft && $this->isComplete($profile)) {
            $profile->profile_status = CompanyProfileStatus::Complete;
        }

        $profile->save();

        return $profile->refresh();
    }

    public function isComplete(CompanyProfile $profile): bool
    {
        return filled($profile->scale_type)
            && filled($profile->nature_type)
            && filled($profile->introduction)
            && filled($profile->logo);
    }

    public function findForCompany(Company $company): ?CompanyProfile
    {
        return CompanyProfile::query()
            ->where('company_id', $company->id)
            ->first();
    }
}
