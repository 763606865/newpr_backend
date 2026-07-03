<?php

namespace App\Rc\Controllers;

use App\Models\Rc\UserCompanyBlacklist;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Rc\Requests\UserCompanyBlacklistStoreRequest;
use App\Rc\Requests\UserCompanyBlacklistUpdateRequest;
use App\Resources\Rc\RcUserCompanyBlacklistResource;
use App\Services\RcIdentityOrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserCompanyBlacklistController extends Controller
{
    /**
     * 求职者企业黑名单列表
     *
     * GET /rc/users/company-blacklists
     *
     * 仅求职者身份可访问；返回当前登录用户维护的企业黑名单，支持按企业名称或统一社会信用代码检索。
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        if (! $this->resolveJobSeekerIdentity($user) instanceof UserIdentity) {
            return $this->jobSeekerIdentityRequiredResponse();
        }

        $keyword = trim((string) $request->input('keyword', ''));

        $paginator = UserCompanyBlacklist::query()
            ->forUser($user)
            ->with('company')
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->whereHas('company', function ($query) use ($keyword): void {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('credit_code', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate($this->getPerPage($request));

        $paginator->getCollection()->transform(
            fn (UserCompanyBlacklist $blacklist): array => (new RcUserCompanyBlacklistResource($blacklist))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 新增企业黑名单
     *
     * POST /rc/users/company-blacklists
     *
     * 仅求职者身份可访问；同一求职者不能重复添加同一家企业。
     */
    public function store(UserCompanyBlacklistStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        if (! $this->resolveJobSeekerIdentity($user) instanceof UserIdentity) {
            return $this->jobSeekerIdentityRequiredResponse();
        }

        $validated = $request->validated();

        $exists = UserCompanyBlacklist::query()
            ->forUser($user)
            ->where('company_id', (int) $validated['company_id'])
            ->exists();

        if ($exists) {
            return $this->error('企业已在黑名单中。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $blacklist = UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => (int) $validated['company_id'],
            'remark' => $validated['remark'] ?? null,
        ]);

        $blacklist->load('company');

        return $this->success([
            'blacklist' => (new RcUserCompanyBlacklistResource($blacklist))->resolve($request),
        ]);
    }

    /**
     * 企业黑名单详情
     *
     * GET /rc/users/company-blacklists/{id}
     *
     * 仅求职者身份可访问；只能查看当前登录用户自己的黑名单记录。
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        if (! $this->resolveJobSeekerIdentity($user) instanceof UserIdentity) {
            return $this->jobSeekerIdentityRequiredResponse();
        }

        $blacklist = $this->findBlacklistForUser($id, $user);

        if (! $blacklist instanceof UserCompanyBlacklist) {
            return $this->error('企业黑名单记录不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'blacklist' => (new RcUserCompanyBlacklistResource($blacklist))->resolve($request),
        ]);
    }

    /**
     * 更新企业黑名单备注
     *
     * PUT/PATCH /rc/users/company-blacklists/{id}
     *
     * 仅求职者身份可访问；只能更新当前登录用户自己的黑名单记录。
     */
    public function update(UserCompanyBlacklistUpdateRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        if (! $this->resolveJobSeekerIdentity($user) instanceof UserIdentity) {
            return $this->jobSeekerIdentityRequiredResponse();
        }

        $blacklist = $this->findBlacklistForUser($id, $user);

        if (! $blacklist instanceof UserCompanyBlacklist) {
            return $this->error('企业黑名单记录不存在。', Response::HTTP_NOT_FOUND);
        }

        $blacklist->update([
            'remark' => $request->validated('remark'),
        ]);

        $blacklist->load('company');

        return $this->success([
            'blacklist' => (new RcUserCompanyBlacklistResource($blacklist))->resolve($request),
        ]);
    }

    /**
     * 删除企业黑名单
     *
     * DELETE /rc/users/company-blacklists/{id}
     *
     * 仅求职者身份可访问；只能删除当前登录用户自己的黑名单记录。
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        if (! $this->resolveJobSeekerIdentity($user) instanceof UserIdentity) {
            return $this->jobSeekerIdentityRequiredResponse();
        }

        $blacklist = $this->findBlacklistForUser($id, $user);

        if (! $blacklist instanceof UserCompanyBlacklist) {
            return $this->error('企业黑名单记录不存在。', Response::HTTP_NOT_FOUND);
        }

        $blacklist->delete();

        return $this->success();
    }

    /**
     * 解析当前用户的求职者身份。
     */
    private function resolveJobSeekerIdentity(User $user): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($user);
    }

    /**
     * 查找当前用户自己的企业黑名单记录。
     */
    private function findBlacklistForUser(int $id, User $user): ?UserCompanyBlacklist
    {
        return UserCompanyBlacklist::query()
            ->forUser($user)
            ->with('company')
            ->find($id);
    }

    /**
     * 非求职者身份访问时的统一业务错误响应。
     */
    private function jobSeekerIdentityRequiredResponse(): JsonResponse
    {
        return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
