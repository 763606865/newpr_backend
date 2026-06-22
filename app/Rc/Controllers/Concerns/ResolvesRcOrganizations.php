<?php

namespace App\Rc\Controllers\Concerns;

use App\Models\Company;
use App\Models\School;
use App\Models\User;
use App\Services\RcJobService;
use App\Services\RcSchoolService;

trait ResolvesRcOrganizations
{
    protected function resolveCampusManagerSchool(): ?School
    {
        /** @var User $user */
        $user = $this->user();

        return RcSchoolService::make()->resolveCampusManagerSchool($user);
    }

    protected function resolveRecruiterCompany(): ?Company
    {
        /** @var User $user */
        $user = $this->user();

        return RcJobService::make()->resolveRecruiterCompany($user);
    }
}
