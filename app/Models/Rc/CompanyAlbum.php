<?php

namespace App\Models\Rc;

use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 企业相册表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property string|null $title 图片标题
 * @property string $image 图片 OSS 路径
 * @property string|null $description 图片描述
 * @property int $type 图片类型：1-办公环境，2-企业文化相册，3-企业荣誉相册，4-其他
 * @property int $sort 排序
 * @property int $status 状态：1-启用，0-停用
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 *
 * @method static Builder forCompany(Company $company)
 * @method static Builder enabled()
 * @method static Builder ordered()
 */
#[Table('rc_company_albums')]
#[Fillable([
    'company_id',
    'title',
    'image',
    'description',
    'type',
    'sort',
    'status',
    'extra',
])]
class CompanyAlbum extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'type' => 1,
        'sort' => 0,
        'status' => 1,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'type' => 'integer',
            'sort' => 'integer',
            'status' => 'integer',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forCompany(Builder $query, Company $company): void
    {
        $query->where($this->getTable().'.company_id', $company->getKey());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', 1);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy($this->getTable().'.sort')
            ->orderByDesc($this->getTable().'.id');
    }
}
