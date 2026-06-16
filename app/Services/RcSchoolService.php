<?php

namespace App\Services;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\SchoolProfileStatus;
use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RcSchoolService extends Service
{
    public function findBySchoolCode(string $schoolCode): ?School
    {
        return School::query()
            ->where('school_code', trim($schoolCode))
            ->first();
    }

    public function resolveCampusManagerIdentity(User $user): ?UserIdentity
    {
        $responsible = $user->token()?->responsible;

        if ($responsible instanceof UserIdentity && $responsible->identity_type === RcIdentityType::CampusManager) {
            return $responsible;
        }

        return $user->identities()
            ->where('identity_type', RcIdentityType::CampusManager)
            ->first();
    }

    public function resolveCampusManagerSchool(User $user): ?School
    {
        $identity = $this->resolveCampusManagerIdentity($user);

        if (! $identity instanceof UserIdentity) {
            return null;
        }

        if ($identity->identity_type !== RcIdentityType::CampusManager || $identity->organization_type !== 'school' || ! $identity->organization_id) {
            return null;
        }

        return School::query()->find($identity->organization_id);
    }

    public function userAlreadyBoundSchoolMessage(User $user, School $school): ?string
    {
        $alreadyBound = $user->identities()
            ->where('identity_type', RcIdentityType::CampusManager)
            ->where('organization_type', 'school')
            ->where('organization_id', $school->id)
            ->exists();

        return $alreadyBound ? '您已绑定该学校。' : null;
    }

    /**
     * 为绑定学校准备可用的校招负责人身份行：未绑定则复用当前行，已绑定则新建一行。
     */
    public function prepareCampusManagerIdentityForSchoolBind(User $user): UserIdentity
    {
        $identity = $this->resolveCampusManagerIdentity($user);

        if (! $identity instanceof UserIdentity) {
            throw new \InvalidArgumentException('Campus manager identity is required.');
        }

        if (! $identity->organization_id) {
            return $identity;
        }

        return $user->identities()->create([
            'identity_type' => RcIdentityType::CampusManager,
            'identity_name' => $identity->identity_name ?? RcIdentityType::CampusManager->getLabel() ?? '校招负责人',
            'is_default' => 0,
            'status' => RcIdentityStatus::Enabled,
        ]);
    }

    public function schoolBindableMessage(School $school): ?string
    {
        if (blank($school->school_code)) {
            return '该学校缺少学校代码，无法绑定。';
        }

        $profile = $school->profile;

        if ($profile !== null && $profile->status === SchoolProfileStatus::Disabled) {
            return '该学校已被禁用，无法绑定。';
        }

        return null;
    }

    public function bind(UserIdentity $identity, School $school, string $jobTitle): UserIdentity
    {
        return DB::transaction(function () use ($identity, $school, $jobTitle): UserIdentity {
            SchoolProfileService::make()->ensureForSchool($school);

            $identity->organization()->associate($school);
            $identity->fill([
                'organization_name' => $school->name,
                'job_title' => $jobTitle,
            ])->save();

            return $identity->refresh();
        });
    }
}
