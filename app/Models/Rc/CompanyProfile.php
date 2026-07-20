<?php

namespace App\Models\Rc;

use App\Enums\CompanyFundingStage;
use App\Enums\CompanyNatureType;
use App\Enums\CompanyProfileStatus;
use App\Enums\CompanyScaleType;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 企业招聘展示资料
 *
 * @property int $id
 * @property int $company_id
 * @property string|null $short_name
 * @property string|null $logo
 * @property string|null $city_code
 * @property CompanyScaleType|null $scale_type
 * @property CompanyNatureType|null $nature_type
 * @property array<int, string>|null $industry_codes
 * @property Carbon|null $founded_at
 * @property string|null $website
 * @property string|null $introduction
 * @property array<int, string>|null $benefit_tags
 * @property CompanyFundingStage|null $funding_stage
 * @property CompanyProfileStatus $profile_status
 * @property bool $is_brand 是否名企
 * @property int $brand_sort 名企排序
 * @property array<string, mixed>|null $extra
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 */
#[Table('rc_company_profiles')]
#[Fillable([
    'company_id',
    'short_name',
    'logo',
    'city_code',
    'scale_type',
    'nature_type',
    'industry_codes',
    'founded_at',
    'website',
    'introduction',
    'benefit_tags',
    'funding_stage',
    'profile_status',
    'is_brand',
    'brand_sort',
    'extra',
])]
class CompanyProfile extends Model
{
    protected $attributes = [
        'profile_status' => CompanyProfileStatus::Draft,
        'is_brand' => false,
        'brand_sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'scale_type' => CompanyScaleType::class,
            'nature_type' => CompanyNatureType::class,
            'industry_codes' => 'array',
            'founded_at' => 'date',
            'benefit_tags' => 'array',
            'funding_stage' => CompanyFundingStage::class,
            'profile_status' => CompanyProfileStatus::class,
            'is_brand' => 'boolean',
            'brand_sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
