<?php

namespace App\Models\Rc;

use App\Enums\RcPortfolioType;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use App\Models\Rc\Concerns\SyncsResumeSearchIndex;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('rc_resume_portfolios')]
#[Fillable([
    'resume_id',
    'user_id',
    'title',
    'type',
    'url',
    'cover_url',
    'description',
    'sort',
    'extra',
])]
class ResumePortfolio extends Model
{
    use SoftDeletes, SyncsResumeSearchIndex;

    protected $attributes = [
        'type' => RcPortfolioType::Link,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'type' => RcPortfolioType::class,
            'url' => AliyunOss::class.':oss,public,3600',
            'cover_url' => AliyunOss::class.':oss,public,3600',
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
