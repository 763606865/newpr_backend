<?php

namespace App\Models\Cms;

use App\Enums\CmsStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户站点配置表
 *
 * @property int $id 主键ID
 * @property string $site_code 站点编码
 * @property string|null $city_code 城市编码
 * @property string $name 站点名称
 * @property string|null $short_name 站点简称
 * @property string|null $domain 站点域名
 * @property string|null $logo 站点Logo
 * @property string|null $favicon 站点图标
 * @property string|null $slogan 站点Slogan
 * @property string|null $icp_no ICP备案号
 * @property string|null $public_security_no 公安备案号
 * @property string|null $service_phone 客服电话
 * @property string|null $service_email 客服邮箱
 * @property string|null $seo_title SEO标题
 * @property string|null $seo_keywords SEO关键词
 * @property string|null $seo_description SEO描述
 * @property CmsStatus $status 状态
 * @property array<string, mixed>|null $theme_config 主题配置
 * @property array<string, mixed>|null $extra 扩展配置
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 *
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder enabled()
 */
#[Table('cms_site_configs')]
#[Fillable([
    'site_code',
    'city_code',
    'name',
    'short_name',
    'domain',
    'logo',
    'favicon',
    'slogan',
    'icp_no',
    'public_security_no',
    'service_phone',
    'service_email',
    'seo_title',
    'seo_keywords',
    'seo_description',
    'status',
    'theme_config',
    'extra',
])]
#[Visible([
    'id',
    'site_code',
    'city_code',
    'name',
    'short_name',
    'domain',
    'logo',
    'favicon',
    'slogan',
    'icp_no',
    'public_security_no',
    'service_phone',
    'service_email',
    'seo_title',
    'seo_keywords',
    'seo_description',
    'status',
    'theme_config',
    'extra',
    'created_at',
    'updated_at',
])]
class SiteConfig extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => CmsStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'logo' => AliyunOss::class.':oss,public,3600',
            'favicon' => AliyunOss::class.':oss,public,3600',
            'status' => CmsStatus::class,
            'theme_config' => 'array',
            'extra' => 'array',
        ];
    }

    #[Scope]
    protected function forCity(Builder $query, ?string $cityCode): void
    {
        if (blank($cityCode)) {
            return;
        }

        $query->where(function (Builder $builder) use ($cityCode): void {
            $builder->where($this->getTable().'.city_code', '=', $cityCode)
                ->orWhereNull($this->getTable().'.city_code');
        });
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }
}
