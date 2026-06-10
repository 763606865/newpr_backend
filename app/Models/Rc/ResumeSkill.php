<?php

namespace App\Models\Rc;

use App\Enums\RcSkillProficiency;
use App\Models\Model;
use App\Models\Rc\Concerns\SyncsResumeSearchIndex;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('rc_resume_skills')]
#[Fillable([
    'resume_id',
    'user_id',
    'skill_name',
    'proficiency',
    'description',
    'sort',
    'extra',
])]
class ResumeSkill extends Model
{
    use SoftDeletes, SyncsResumeSearchIndex;

    protected $attributes = [
        'proficiency' => RcSkillProficiency::Familiar,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'proficiency' => RcSkillProficiency::class,
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
