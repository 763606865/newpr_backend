<?php

namespace App\Rc\Controllers;

use App\Models\Company;
use App\Models\Rc\CompanyAlbum;
use App\Models\User;
use App\Rc\Requests\CompanyAlbumStoreRequest;
use App\Rc\Requests\CompanyAlbumUpdateRequest;
use App\Resources\Rc\RcCompanyAlbumResource;
use App\Services\RcJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyAlbumController extends Controller
{
    /**
     * 当前企业相册列表
     *
     * GET /rc/companies/albums
     *
     * 仅招聘方身份且已绑定企业可访问；返回当前企业的相册图片，支持按类型、状态和关键词筛选。
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->companyRequiredResponse();
        }

        $keyword = trim((string) $request->input('keyword', ''));

        $paginator = CompanyAlbum::query()
            ->forCompany($company)
            ->when($request->filled('type'), fn ($query): mixed => $query->where('type', (int) $request->input('type')))
            ->when($request->filled('status'), fn ($query): mixed => $query->where('status', (int) $request->input('status')))
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            })
            ->ordered()
            ->paginate($this->getPerPage($request));

        $paginator->getCollection()->transform(
            fn (CompanyAlbum $album): array => (new RcCompanyAlbumResource($album))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 新增当前企业相册图片
     *
     * POST /rc/companies/albums
     *
     * 仅招聘方身份且已绑定企业可访问；图片文件需先通过上传接口拿到 OSS 路径。
     */
    public function store(CompanyAlbumStoreRequest $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->companyRequiredResponse();
        }

        $album = CompanyAlbum::query()->create(array_merge($request->validated(), [
            'company_id' => $company->id,
        ]));

        return $this->success([
            'album' => (new RcCompanyAlbumResource($album))->resolve($request),
        ]);
    }

    /**
     * 当前企业相册图片详情
     *
     * GET /rc/companies/albums/{id}
     *
     * 仅招聘方身份且已绑定企业可访问；只能查看当前企业自己的相册图片。
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->companyRequiredResponse();
        }

        $album = $this->findAlbumForCompany($id, $company);

        if (! $album instanceof CompanyAlbum) {
            return $this->albumNotFoundResponse();
        }

        return $this->success([
            'album' => (new RcCompanyAlbumResource($album))->resolve($request),
        ]);
    }

    /**
     * 更新当前企业相册图片
     *
     * PUT/PATCH /rc/companies/albums/{id}
     *
     * 仅招聘方身份且已绑定企业可访问；只能更新当前企业自己的相册图片。
     */
    public function update(CompanyAlbumUpdateRequest $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->companyRequiredResponse();
        }

        $album = $this->findAlbumForCompany($id, $company);

        if (! $album instanceof CompanyAlbum) {
            return $this->albumNotFoundResponse();
        }

        $album->update($request->validated());

        return $this->success([
            'album' => (new RcCompanyAlbumResource($album))->resolve($request),
        ]);
    }

    /**
     * 删除当前企业相册图片
     *
     * DELETE /rc/companies/albums/{id}
     *
     * 仅招聘方身份且已绑定企业可访问；删除采用软删除。
     */
    public function destroy(int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->companyRequiredResponse();
        }

        $album = $this->findAlbumForCompany($id, $company);

        if (! $album instanceof CompanyAlbum) {
            return $this->albumNotFoundResponse();
        }

        $album->delete();

        return $this->success();
    }

    private function resolveCompany(): ?Company
    {
        /** @var User $user */
        $user = $this->user();

        return RcJobService::make()->resolveRecruiterCompany($user);
    }

    private function findAlbumForCompany(int $id, Company $company): ?CompanyAlbum
    {
        return CompanyAlbum::query()
            ->forCompany($company)
            ->find($id);
    }

    private function companyRequiredResponse(): JsonResponse
    {
        return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function albumNotFoundResponse(): JsonResponse
    {
        return $this->error('企业相册不存在。', Response::HTTP_NOT_FOUND);
    }
}
