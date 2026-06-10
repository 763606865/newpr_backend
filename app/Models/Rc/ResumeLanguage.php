<?php

namespace App\Models\Rc;

use App\Enums\RcLanguageProficiency;
use App\Models\Model;
use App\Models\Rc\Concerns\SyncsResumeSearchIndex;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('rc_resume_languages')]
#[Fillable([
    'resume_id',
    'user_id',
    'language',
    'proficiency',
    'certificate',
    'sort',
    'extra',
])]
class ResumeLanguage extends Model
{
    use SoftDeletes, SyncsResumeSearchIndex;

    protected $attributes = [
        'proficiency' => RcLanguageProficiency::Conversational,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'proficiency' => RcLanguageProficiency::class,
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
