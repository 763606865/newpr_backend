<?php

namespace App\Models\Rc;

use App\Enums\RcCertificateType;
use App\Models\Model;
use App\Models\Rc\Concerns\SyncsResumeSearchIndex;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Table('rc_resume_certificates')]
#[Fillable([
    'resume_id',
    'user_id',
    'name',
    'cert_type',
    'issuer',
    'issue_date',
    'expire_date',
    'cert_no',
    'description',
    'sort',
    'extra',
])]
class ResumeCertificate extends Model
{
    use SoftDeletes, SyncsResumeSearchIndex;

    protected $attributes = [
        'cert_type' => RcCertificateType::Certificate,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'cert_type' => RcCertificateType::class,
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    protected function issueDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn (mixed $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function expireDate(): Attribute
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
