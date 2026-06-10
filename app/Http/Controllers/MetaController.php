<?php

namespace App\Http\Controllers;

use App\Services\MetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function __construct(private readonly MetaService $metaService) {}

    /**
     * 门户地区元数据
     *
     * GET /cms/meta
     *
     * 无需鉴权。返回全国行政区划树，供门户城市选择器及 `city_code` 参数取值使用。
     *
     * 响应 `data` 结构：
     * - `areas`：地区树，字段见 `RcAreaResource`（含 `children` 子节点）
     * - `majors`：专业树，字段见 `RcMajorResource`（含 `children` 子节点）
     * - `major_levels` / `major_education_types`：专业层级与学历类型字典
     *
     * @see docs/Cms/Meta.md
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'areas' => $this->areasPayload(),
            'majors' => $this->majorsPayload(),
            ...$this->metaService->getMajorDictionaries(),
        ]);
    }

    /**
     * 专业元数据
     *
     * GET /cms/meta/majors
     *
     * 无需鉴权。返回专业树及学历类型、层级字典，供高校校园侧专业选择器使用。
     *
     * @see docs/Cms/Meta.md
     *
     * @throws \Exception
     */
    public function majors(Request $request): JsonResponse
    {
        return $this->success([
            'majors' => $this->majorsPayload(),
            ...$this->metaService->getMajorDictionaries(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function areasPayload(): array
    {
        return $this->metaService->getAreasTree();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function majorsPayload(): array
    {
        return $this->metaService->getMajorsTree();
    }
}
