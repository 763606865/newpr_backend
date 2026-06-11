<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\CompanyFavorite;
use App\Models\Rc\UserIdentity;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcCompanyDiscoveryResource;
use App\Services\RcCompanyFavoriteService;
use App\Services\RcIdentityOrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class CompanyFavoriteController extends Controller
{
    /**
     * 我收藏的企业列表
     *
     * GET /rc/talent/favorites/companies
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcCompanyFavoriteService::make()->paginateForUser(
            $this->user(),
            $this->getPerPage($request),
        );

        $paginator->getCollection()->transform(
            function (CompanyFavorite $favorite) use ($request): array {
                $data = (new RcCompanyDiscoveryResource($favorite->company))->resolve($request);
                $data['is_favorited'] = true;
                $data['favorited_at'] = $favorite->created_at;

                return $data;
            },
        );

        return $this->success($paginator);
    }

    /**
     * 收藏企业
     *
     * POST /rc/talent/companies/{id}/favorite
     */
    public function store(int $id): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $company = RcCompanyFavoriteService::make()->resolveDiscoverableCompanyOrFail($id);
            RcCompanyFavoriteService::make()->favorite($this->user(), $company);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'is_favorited' => true,
        ]);
    }

    /**
     * 取消收藏企业
     *
     * DELETE /rc/talent/companies/{id}/favorite
     */
    public function destroy(int $id): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $company = RcCompanyFavoriteService::make()->resolveDiscoverableCompanyOrFail($id);
            RcCompanyFavoriteService::make()->unfavorite($this->user(), $company);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'is_favorited' => false,
        ]);
    }

    private function resolveJobSeekerIdentity(): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($this->user());
    }
}
