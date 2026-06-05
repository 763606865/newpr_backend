<?php

namespace App\Models;

use App\Enums\CompanyContactType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企业联系人/股东表
 *
 * @property int $id
 * @property int $company_id
 * @property CompanyContactType $contact_type
 * @property string $name
 * @property string|null $id_card
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $position
 * @property string|null $share_ratio
 * @property string|null $address
 * @property int $is_primary
 * @property int $sort
 * @property int $status
 * @property string|null $remark
 * @property array<string, mixed>|null $extra
 */
#[Table('company_contacts')]
#[Fillable([
    'company_id',
    'contact_type',
    'name',
    'id_card',
    'phone',
    'email',
    'position',
    'share_ratio',
    'address',
    'is_primary',
    'sort',
    'status',
    'remark',
    'extra',
])]
class CompanyContact extends Model
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
            'contact_type' => CompanyContactType::class,
            'share_ratio' => 'decimal:2',
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
