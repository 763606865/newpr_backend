<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Rc\Resume;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class RcResumeExposureStatsService extends Service
{
    /**
     * @param  Collection<int, Resume>  $promotedResumes
     */
    public function recordImpressions(Collection $promotedResumes, Company $company): void
    {
        if ($promotedResumes->isEmpty()) {
            return;
        }

        try {
            foreach ($promotedResumes as $resume) {
                $exposureId = (int) $resume->getAttribute('promotion_id');

                if ($exposureId < 1) {
                    continue;
                }

                $now = now();
                DB::table('rc_resume_exposure_stats_daily')->insertOrIgnore([
                    [
                        'exposure_id' => $exposureId,
                        'resume_id' => $resume->id,
                        'company_id' => $company->id,
                        'stat_date' => $now->toDateString(),
                        'impressions' => 0,
                        'detail_views' => 0,
                        'contacts' => 0,
                        'favorites' => 0,
                        'invitations' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);

                DB::table('rc_resume_exposure_stats_daily')
                    ->where('exposure_id', $exposureId)
                    ->where('company_id', $company->id)
                    ->where('stat_date', $now->toDateString())
                    ->increment('impressions', 1, ['updated_at' => $now]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
