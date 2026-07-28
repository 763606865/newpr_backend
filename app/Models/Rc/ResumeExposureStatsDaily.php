<?php

namespace App\Models\Rc;

use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * RC 简历曝光效果日统计表
 *
 * @property int $id
 * @property int $exposure_id
 * @property int $resume_id
 * @property int $company_id
 * @property Carbon $stat_date
 * @property int $impressions
 * @property int $detail_views
 * @property int $contacts
 * @property int $favorites
 * @property int $invitations
 * @property-read ResumeExposure $exposure
 * @property-read Resume $resume
 * @property-read Company $company
 */
#[Table('rc_resume_exposure_stats_daily')]
#[Fillable([
    'exposure_id',
    'resume_id',
    'company_id',
    'stat_date',
    'impressions',
    'detail_views',
    'contacts',
    'favorites',
    'invitations',
])]
class ResumeExposureStatsDaily extends Model
{
    protected $attributes = [
        'impressions' => 0,
        'detail_views' => 0,
        'contacts' => 0,
        'favorites' => 0,
        'invitations' => 0,
    ];

    protected function casts(): array
    {
        return [
            'exposure_id' => 'integer',
            'resume_id' => 'integer',
            'company_id' => 'integer',
            'stat_date' => 'date',
            'impressions' => 'integer',
            'detail_views' => 'integer',
            'contacts' => 'integer',
            'favorites' => 'integer',
            'invitations' => 'integer',
        ];
    }

    public function exposure(): BelongsTo
    {
        return $this->belongsTo(ResumeExposure::class, 'exposure_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
