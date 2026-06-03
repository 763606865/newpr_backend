<?php

namespace App\Models\Rc;

use App\Enums\RcEmploymentType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'integer',
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
