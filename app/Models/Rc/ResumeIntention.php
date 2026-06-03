<?php

namespace App\Models\Rc;

use App\Enums\RcEmploymentType;
use App\Enums\RcResumeJobStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
            'extra' => 'array',
        ];
    }

    protected function availableDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn (mixed $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
