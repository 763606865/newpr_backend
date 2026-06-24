<?php

namespace App\Services;

use App\Enums\CmsMenuAudienceType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Cms\Menu;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CmsMenuAudienceService extends Service
{
    public function resolveAudience(Request $request): CmsMenuAudienceType
    {
        return CmsMenuAudienceType::fromRcIdentity($this->resolveRcIdentityType($request));
    }

    public function resolveRcIdentityType(Request $request): ?RcIdentityType
    {
        $user = $request->user('rc');

        if (! $user instanceof User) {
            return null;
        }

        $identity = RcIdentityOrganizationService::make()->resolveCurrentIdentity($user);

        if ($identity instanceof UserIdentity) {
            return $identity->identity_type;
        }

        $fallbackIdentity = $user->defaultIdentity()->first()
            ?? $user->identities()
                ->where('status', RcIdentityStatus::Enabled)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

        return $fallbackIdentity instanceof UserIdentity ? $fallbackIdentity->identity_type : null;
    }

    public function isRouteAccessible(string $routeName, CmsMenuAudienceType $audience): bool
    {
        $menu = $this->findMenuByCode($routeName);

        if ($menu === null) {
            return true;
        }

        return $menu->isVisibleToAudience($audience);
    }

    public function assertRouteAccessible(Request $request, ?string $routeName = null): void
    {
        $routeName ??= (string) $request->route()?->getName();

        if ($routeName === '') {
            return;
        }

        if ($this->isRouteAccessible($routeName, $this->resolveAudience($request))) {
            return;
        }

        throw new AccessDeniedHttpException('当前身份无权访问该内容。');
    }

    public function findMenuByCode(string $code): ?Menu
    {
        return Menu::query()
            ->enabled()
            ->shown()
            ->where('code', $code)
            ->with('menuIdentities')
            ->first();
    }
}
