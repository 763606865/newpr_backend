<?php

namespace App\Services;

use App\Enums\SchoolProfileStatus;
use App\Models\School;
use App\Models\SchoolProfile;
use Illuminate\Support\Arr;

class SchoolProfileService extends Service
{
    public function ensureForSchool(School $school): SchoolProfile
    {
        return SchoolProfile::query()->firstOrCreate(
            ['school_code' => $school->school_code],
            [
                'competent_dept' => $school->competent_dept,
                'address' => $school->address,
                'status' => SchoolProfileStatus::Reviewing,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SchoolProfile $profile, array $data): SchoolProfile
    {
        if (array_key_exists('official_logo', $data)) {
            $profile->school?->update([
                'official_logo' => $data['official_logo'],
            ]);
        }

        $profile->fill(Arr::only($data, [
            'short_name',
            'province_code',
            'city_code',
            'district_code',
            'address',
            'contact_name',
            'contact_phone',
            'contact_email',
            'qualification_file',
            'competent_dept',
            'education_levels',
            'main_education_level',
            'logo',
            'banner',
            'allow_company_apply_activity',
            'allow_company_cooperate_apply',
            'intro',
            'remark',
        ]));

        if ($profile->status === SchoolProfileStatus::Reviewing && $this->isComplete($profile)) {
            $profile->status = SchoolProfileStatus::Normal;
        }

        $profile->save();

        return $profile->refresh()->load('school');
    }

    public function isComplete(SchoolProfile $profile): bool
    {
        return filled($profile->intro)
            && filled($profile->logo)
            && filled($profile->contact_name)
            && filled($profile->contact_phone);
    }

    public function findForSchool(School $school): ?SchoolProfile
    {
        if (blank($school->school_code)) {
            return null;
        }

        return SchoolProfile::query()
            ->where('school_code', $school->school_code)
            ->first();
    }
}
