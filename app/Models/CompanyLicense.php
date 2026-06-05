<?php

namespace App\Models;

use App\Enums\CompanyLicenseType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企业证件表
 *
 * @property int $id
 * @property int $company_id
 * @property CompanyLicenseType $license_type
 * @property string $name
 * @property string|null $license_no
 * @property string|null $issuer
 * @property string|null $issue_date
 * @property string|null $expire_date
 * @property string|null $file_url
 * @property string|null $file_name
 * @property string|null $file_ext
 * @property int $is_primary
 * @property int $sort
 * @property int $status
 * @property string|null $remark
 * @property array<string, mixed>|null $extra
 */
#[Table('company_licenses')]
#[Fillable([
    'company_id',
    'license_type',
    'name',
    'license_no',
    'issuer',
    'issue_date',
    'expire_date',
    'file_url',
    'file_name',
    'file_ext',
    'is_primary',
    'sort',
    'status',
    'remark',
    'extra',
])]
class CompanyLicense extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'is_primary' => 0,
        'sort' => 0,
        'status' => 1,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'license_type' => CompanyLicenseType::class,
            'is_primary' => 'integer',
            'sort' => 'integer',
            'status' => 'integer',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
