<?php

namespace App\Services;

use App\Enums\CompanyBenefitTag;
use App\Enums\CompanyFundingStage;
use App\Enums\CompanyNatureType;
use App\Enums\CompanyScaleType;
use App\Models\Area;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Resources\Rc\RcAreaResource;
use App\Resources\Rc\RcIndustryResource;
use App\Resources\Rc\RcPositionResource;
use App\Support\EnumOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MetaService extends Service
{
    private const AREAS_TREE_CACHE_KEY = 'rc:meta:areas:tree';

    private const AREAS_MAP_CACHE_KEY = 'rc:meta:areas:map';

    private const AREAS_INDEX_CACHE_KEY = 'rc:meta:areas:index';

    private const INDUSTRIES_TREE_CACHE_KEY = 'rc:meta:industries:tree';

    private const POSITIONS_TREE_CACHE_KEY = 'rc:meta:positions:tree';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAreasTree(): array
    {
        return Cache::rememberForever(self::AREAS_TREE_CACHE_KEY, function (): array {
            $areas = Area::query()
                ->orderBy('level')
                ->orderBy('code')
                ->get();

            return tree(
                RcAreaResource::collection($areas)->resolve(new Request),
                'parent_code',
                'code',
                '',
            );
        });
    }

    /**
     * @return array<string, string>
     */
    public function getAreaNameMap(): array
    {
        return Cache::rememberForever(self::AREAS_MAP_CACHE_KEY, function (): array {
            return Area::query()
                ->orderBy('level')
                ->orderBy('code')
                ->pluck('name', 'code')
                ->all();
        });
    }

    /**
     * @return array<string, array{name: string, parent_code: string|null, level: int}>
     */
    public function getAreaIndex(): array
    {
        return Cache::rememberForever(self::AREAS_INDEX_CACHE_KEY, function (): array {
            return Area::query()
                ->orderBy('level')
                ->orderBy('code')
                ->get()
                ->mapWithKeys(fn (Area $area): array => [
                    $area->code => [
                        'name' => $area->name,
                        'parent_code' => $area->parent_code,
                        'level' => $area->level->value,
                    ],
                ])
                ->all();
        });
    }

    /**
     * 根据市级区划 code 解析完整地名（如：中国江西省南昌市）。
     */
    public function getCityFullName(string $cityCode): ?string
    {
        $index = $this->getAreaIndex();

        if (! isset($index[$cityCode])) {
            return null;
        }

        $names = [];
        $code = $cityCode;

        while (isset($index[$code]) && $code !== '000000') {
            $names[] = $index[$code]['name'];
            $parentCode = $index[$code]['parent_code'];

            if (blank($parentCode) || $parentCode === '000000') {
                break;
            }

            $code = $parentCode;
        }

        if ($names === []) {
            return null;
        }

        return '中国'.implode('', array_reverse($names));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getIndustriesTree(): array
    {
        return Cache::rememberForever(self::INDUSTRIES_TREE_CACHE_KEY, function (): array {
            $industries = Industry::query()
                ->orderBy('sort')
                ->orderBy('id')
                ->get();

            return tree(RcIndustryResource::collection($industries)->resolve(new Request));
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPositionsTree(): array
    {
        return Cache::rememberForever(self::POSITIONS_TREE_CACHE_KEY, function (): array {
            $positions = Position::query()
                ->orderBy('sort')
                ->orderBy('id')
                ->get();

            return tree(RcPositionResource::collection($positions)->resolve(new Request));
        });
    }

    /**
     * @return array<int, array{value: int|string, label: string|null}>
     */
    public function getCompanyScales(): array
    {
        return EnumOptions::from(CompanyScaleType::class);
    }

    /**
     * @return array<int, array{value: int|string, label: string|null}>
     */
    public function getCompanyNatures(): array
    {
        return EnumOptions::from(CompanyNatureType::class);
    }

    /**
     * @return array<int, array{value: int|string, label: string|null}>
     */
    public function getCompanyFundingStages(): array
    {
        return EnumOptions::from(CompanyFundingStage::class);
    }

    /**
     * @return array<int, array{value: int|string, label: string|null}>
     */
    public function getCompanyBenefitTags(): array
    {
        return EnumOptions::from(CompanyBenefitTag::class);
    }

    /**
     * @return array<string, array<int, array{value: int|string, label: string|null}>>
     */
    public function getCompanyDictionaries(): array
    {
        return [
            'company_scales' => $this->getCompanyScales(),
            'company_natures' => $this->getCompanyNatures(),
            'company_funding_stages' => $this->getCompanyFundingStages(),
            'company_benefit_tags' => $this->getCompanyBenefitTags(),
        ];
    }

    public function forgetAreas(): void
    {
        Cache::forget(self::AREAS_TREE_CACHE_KEY);
        Cache::forget(self::AREAS_MAP_CACHE_KEY);
        Cache::forget(self::AREAS_INDEX_CACHE_KEY);
    }

    public function forgetIndustries(): void
    {
        Cache::forget(self::INDUSTRIES_TREE_CACHE_KEY);
    }

    public function forgetPositions(): void
    {
        Cache::forget(self::POSITIONS_TREE_CACHE_KEY);
    }

    public function forgetAll(): void
    {
        $this->forgetAreas();
        $this->forgetIndustries();
        $this->forgetPositions();
    }
}
