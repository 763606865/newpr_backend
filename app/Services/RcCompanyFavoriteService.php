<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Rc\CompanyFavorite;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class RcCompanyFavoriteService extends Service
{
    public function favorite(User $user, Company $company): void
    {
        CompanyFavorite::query()->firstOrCreate([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    public function unfavorite(User $user, Company $company): void
    {
        CompanyFavorite::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->delete();
    }

    public function isFavorited(User $user, int $companyId): bool
    {
        return CompanyFavorite::query()
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->exists();
    }

    /**
     * @return LengthAwarePaginator<int, CompanyFavorite>
     */
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return CompanyFavorite::query()
            ->where('user_id', $user->id)
            ->whereHas('company')
            ->with(['company.profile'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function resolveDiscoverableCompanyOrFail(int $companyId): Company
    {
        $company = RcCompanyDiscoveryService::make()->findDiscoverableCompany($companyId);

        if (! $company instanceof Company) {
            throw new InvalidArgumentException('企业不存在或不可查看。');
        }

        return $company;
    }
}
