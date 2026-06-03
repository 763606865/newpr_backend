<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Resources\Rc\RcAreaResource;
use App\Resources\Rc\RcIndustryResource;
use App\Resources\Rc\RcPositionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MetaService extends Service
{
    private const AREAS_TREE_CACHE_KEY = 'rc:meta:areas:tree';

    private const AREAS_MAP_CACHE_KEY = 'rc:meta:areas:map';

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

    public function forgetAreas(): void
    {
        Cache::forget(self::AREAS_TREE_CACHE_KEY);
        Cache::forget(self::AREAS_MAP_CACHE_KEY);
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
