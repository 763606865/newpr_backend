<?php

namespace App\Models\Rc;

use App\Enums\RcEducationLevel;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Table('rc_resume_educations')]
#[Fillable([
    'resume_id',
    'user_id',
    'school_name',
    'major',
    'degree',
    'education_type',
    'start_date',
    'end_date',
    'is_current',
    'description',
    'sort',
    'extra',
])]
class ResumeEducation extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'education_type' => 1,
        'is_current' => 0,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'degree' => RcEducationLevel::class,
            'education_type' => 'integer',
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
