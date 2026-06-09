<?php

namespace App\Rc\Controllers;

use App\Services\MetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function __construct(private readonly MetaService $metaService) {}

    /**
     * 简历填写元数据汇总
     *
     * GET /rc/meta
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'areas' => $this->areasPayload(),
            'industries' => $this->industriesPayload(),
            'positions' => $this->positionsPayload(),
            ...$this->metaService->getCompanyDictionaries(),
        ]);
    }

    /**
     * 城市元数据
     *
     * GET /rc/meta/areas
     *
     * @throws \Exception
     */
    public function areas(Request $request): JsonResponse
    {
        return $this->success([
            'areas' => $this->areasPayload(),
        ]);
    }

    /**
     * 常用行业元数据
     *
     * GET /rc/meta/industries
     *
     * @throws \Exception
     */
    public function industries(Request $request): JsonResponse
    {
        return $this->success([
            'industries' => $this->industriesPayload(),
        ]);
    }

    /**
     * 常用职位元数据
     *
     * GET /rc/meta/positions
     *
     * @throws \Exception
     */
    public function positions(Request $request): JsonResponse
    {
        return $this->success([
            'positions' => $this->positionsPayload(),
        ]);
    }

    /**
     * 企业资料字典
     *
     * GET /rc/meta/companies
     *
     * @throws \Exception
     */
    public function companies(Request $request): JsonResponse
    {
        return $this->success($this->metaService->getCompanyDictionaries());
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
    private function industriesPayload(): array
    {
        return $this->metaService->getIndustriesTree();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function positionsPayload(): array
    {
        return $this->metaService->getPositionsTree();
    }
}
