<?php

namespace App\Models\Rc;

use App\Enums\RcEmploymentType;
use App\Enums\RcResumeJobStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('rc_resume_intentions')]
#[Fillable([
    'resume_id',
    'user_id',
    'job_status',
    'employment_type',
    'expected_city_code',
    'expected_industry_codes',
    'expected_position_id',
    'salary_min',
    'salary_max',
    'salary_unit',
    'available_date',
    'extra',
])]
class ResumeIntention extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'job_status' => RcResumeJobStatus::OpenToOpportunity,
        'salary_unit' => RcSalaryUnit::Month,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'job_status' => RcResumeJobStatus::class,
            'employment_type' => RcEmploymentType::class,
            'expected_industry_codes' => 'array',
            'expected_position_id' => 'integer',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'salary_unit' => RcSalaryUnit::class,
            'available_date' => 'date',
            'extra' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
