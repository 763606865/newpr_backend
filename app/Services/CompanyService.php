<?php

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Models\Company;

class CompanyService extends Service
{
    public function create(array $params = [])
    {
        $params['status'] = $this->getGuardName() === 'admin' ? CompanyStatus::Enabled : CompanyStatus::Auditing;

        return Company::query()->firstOrCreate([
            'name' => $params['name'],
            'credit_code' => $params['credit_code'] ?? null,
        ], $params);
    }
}
