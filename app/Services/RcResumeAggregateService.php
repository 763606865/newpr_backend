<?php

namespace App\Services;

use App\Enums\RcCurrentIdentity;
use App\Enums\RcEducationLevel;
use App\Enums\RcResumeJobStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeIntention;
use App\Models\Rc\ResumeWork;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RcResumeAggregateService extends Service
{
    public function sync(Resume $resume): Resume
    {
        $resume->load([
            'intentions' => fn ($query) => $query->orderByDesc('updated_at')->orderByDesc('id'),
            'educations',
            'works',
        ]);

        $primaryIntention = $resume->intentions->first();
        $workStartDate = $this->resolveWorkStartDate($resume);
        $workYears = $this->resolveWorkYears($workStartDate);
        $currentIdentity = $this->resolveCurrentIdentity($resume, $primaryIntention);

        $resume->forceFill([
            'highest_education_level' => $this->resolveHighestEducationLevel($resume),
            'expected_salary_min' => $primaryIntention?->salary_min,
            'expected_salary_max' => $primaryIntention?->salary_max,
            'expected_salary_unit' => $primaryIntention?->salary_unit ?? RcSalaryUnit::Month,
            'work_start_date' => $workStartDate,
            'work_years' => $workYears,
            'current_identity' => $currentIdentity,
            'is_fresh_graduate' => Resume::resolvesFreshGraduate($currentIdentity, $workYears) ? 1 : 0,
        ])->saveQuietly();

        if ($resume->shouldBeSearchable()) {
            $resume->searchable();
        } else {
            $resume->unsearchable();
        }

        return $resume->refresh();
    }

    private function resolveHighestEducationLevel(Resume $resume): ?RcEducationLevel
    {
        /** @var Collection<int, RcEducationLevel> $levels */
        $levels = $resume->educations
            ->pluck('degree')
            ->filter()
            ->map(fn (mixed $degree): ?RcEducationLevel => $degree instanceof RcEducationLevel
                ? $degree
                : RcEducationLevel::tryFrom((int) $degree))
            ->filter();

        if ($levels->isEmpty()) {
            return null;
        }

        return $levels->sortByDesc(fn (RcEducationLevel $level): int => $this->educationRank($level))->first();
    }

    private function educationRank(RcEducationLevel $level): int
    {
        return match ($level) {
            RcEducationLevel::Doctor => 5,
            RcEducationLevel::Master => 4,
            RcEducationLevel::Bachelor => 3,
            RcEducationLevel::Associate => 2,
            RcEducationLevel::HighSchool => 1,
            RcEducationLevel::Other => 0,
        };
    }

    private function resolveWorkStartDate(Resume $resume): ?string
    {
        $earliest = $resume->works
            ->filter(fn (ResumeWork $work): bool => filled($work->start_date))
            ->sortBy(fn (ResumeWork $work): string => (string) $work->start_date)
            ->first();

        return $earliest instanceof ResumeWork ? $earliest->start_date : null;
    }

    private function resolveWorkYears(?string $workStartDate): ?int
    {
        if (blank($workStartDate)) {
            return null;
        }

        $years = (int) Carbon::parse($workStartDate)->diffInYears(Carbon::now());

        return min(max($years, 0), 80);
    }

    private function resolveCurrentIdentity(Resume $resume, ?ResumeIntention $primaryIntention): RcCurrentIdentity
    {
        if ($resume->works->isNotEmpty()) {
            return RcCurrentIdentity::WorkingPerson;
        }

        if ($primaryIntention?->job_status === RcResumeJobStatus::FreshGraduate) {
            return RcCurrentIdentity::Student;
        }

        if ($resume->educations->contains(
            fn (ResumeEducation $education): bool => (int) $education->is_current === 1,
        )) {
            return RcCurrentIdentity::Student;
        }

        if ($primaryIntention?->job_status === RcResumeJobStatus::ActivelyLooking) {
            return RcCurrentIdentity::Unemployed;
        }

        if ($primaryIntention?->job_status === RcResumeJobStatus::OpenToOpportunity) {
            return RcCurrentIdentity::WorkingPerson;
        }

        return RcCurrentIdentity::Other;
    }
}
