<?php

namespace App\Models\Rc;

use App\Enums\RcEmploymentType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Table('rc_resume_works')]
#[Fillable([
    'resume_id',
    'user_id',
    'company_name',
    'department',
    'position',
    'employment_type',
    'start_date',
    'end_date',
    'is_current',
    'description',
    'sort',
    'extra',
])]
class ResumeWork extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'employment_type' => RcEmploymentType::FullTime,
        'is_current' => 0,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'employment_type' => RcEmploymentType::class,
            'is_current' => 'integer',
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn (mixed $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function endDate(): Attribute
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
