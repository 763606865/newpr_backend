<?php

namespace App\Services;

use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeFavorite;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class RcResumeFavoriteService extends Service
{
    public function favorite(User $user, Company $company, Resume $resume): void
    {
        ResumeFavorite::query()->firstOrCreate([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'resume_id' => $resume->id,
        ]);
    }

    public function unfavorite(User $user, Company $company, Resume $resume): void
    {
        ResumeFavorite::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('resume_id', $resume->id)
            ->delete();
    }

    public function isFavorited(User $user, int $companyId, int $resumeId): bool
    {
        return ResumeFavorite::query()
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->where('resume_id', $resumeId)
            ->exists();
    }

    /**
     * @return LengthAwarePaginator<int, ResumeFavorite>
     */
    public function paginateForUser(User $user, Company $company, int $perPage): LengthAwarePaginator
    {
        return ResumeFavorite::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->whereHas('resume', function ($query): void {
                $query->where('status', RcResumeStatus::Normal->value);
            })
            ->with(['resume'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function resolveDiscoverableResumeOrFail(int $resumeId): Resume
    {
        $resume = RcResumeDiscoveryService::make()->findDiscoverableResume($resumeId);

        if (! $resume instanceof Resume) {
            throw new InvalidArgumentException('简历不存在或不可查看。');
        }

        return $resume;
    }
}
