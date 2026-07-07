<?php

namespace App\Enums;

use App\Models\Rc\SchoolActivity;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum RcSchoolActivityBusinessStatus: int implements HasLabel
{
    case Draft = 0;
    case Upcoming = 1;
    case Registering = 2;
    case Ongoing = 3;
    case Ended = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Upcoming => '未开始',
            self::Registering => '报名中',
            self::Ongoing => '进行中',
            self::Ended => '已结束',
        };
    }

    public static function fromActivity(SchoolActivity $activity): self
    {
        if ($activity->status === RcSchoolActivityStatus::Draft) {
            return self::Draft;
        }

        if ($activity->status === RcSchoolActivityStatus::Ended) {
            return self::Ended;
        }

        $now = Carbon::now();

        if ($activity->end_time !== null && $activity->end_time->lessThan($now)) {
            return self::Ended;
        }

        if ($activity->start_time !== null && $activity->start_time->lessThanOrEqualTo($now)) {
            if ($activity->end_time === null || $activity->end_time->greaterThanOrEqualTo($now)) {
                return self::Ongoing;
            }
        }

        if (
            $activity->start_time !== null
            && $activity->start_time->greaterThan($now)
            && $activity->register_start_date === null
            && $activity->register_end_date === null
        ) {
            return self::Upcoming;
        }

        if ($activity->register_start_date !== null && $activity->register_start_date->greaterThan($now)) {
            return self::Upcoming;
        }

        if (
            $activity->register_start_date !== null
            || $activity->register_end_date !== null
        ) {
            if (
                ($activity->register_start_date === null || $activity->register_start_date->lessThanOrEqualTo($now))
                && ($activity->register_end_date === null || $activity->register_end_date->greaterThanOrEqualTo($now))
            ) {
                return self::Registering;
            }
        } else {
            return self::Registering;
        }

        if ($activity->start_time !== null && $activity->start_time->greaterThan($now)) {
            return self::Upcoming;
        }

        return self::Ended;
    }
}
