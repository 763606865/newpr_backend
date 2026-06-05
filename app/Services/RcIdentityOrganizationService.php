<?php

namespace App\Services;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Area;
use App\Models\Company;
use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\User;
use App\Resources\Rc\RcIdentityOrganizationItemResource;
use Illuminate\Support\Collection;

class RcIdentityOrganizationService extends Service
{
    public function resolveCurrentIdentity(User $user): ?UserIdentity
    {
        $responsible = $user->token()?->responsible;

        return $responsible instanceof UserIdentity ? $responsible : null;
    }

    public function resolveJobSeekerIdentity(User $user): ?UserIdentity
    {
        $responsible = $user->token()?->responsible;

        if ($responsible instanceof UserIdentity && $responsible->identity_type === RcIdentityType::JobSeeker) {
            return $responsible;
        }

        return $user->identities()
            ->where('identity_type', RcIdentityType::JobSeeker)
            ->first();
    }

    /**
     * @return array{
     *     identity_type: int,
     *     identity_type_label: string|null,
     *     organization_type: string|null,
     *     items: list<array<string, mixed>>
     * }
     */
    public function listForIdentity(User $user, UserIdentity $currentIdentity): array
    {
        $identityType = $currentIdentity->identity_type;

        if (! $identityType instanceof RcIdentityType) {
            return [
                'identity_type' => 0,
                'identity_type_label' => null,
                'organization_type' => null,
                'items' => [],
            ];
        }

        $organizationType = $identityType->organizationMorphType();

        $query = $user->identities()
            ->where('identity_type', $identityType)
            ->where('status', RcIdentityStatus::Enabled)
            ->whereNotNull('organization_id');

        if ($organizationType !== null) {
            $query->where('organization_type', $organizationType);
        }

        /** @var Collection<int, UserIdentity> $identities */
        $identities = $query
            ->with([
                'organization' => function ($morphTo): void {
                    $morphTo->morphWith([
                        Company::class => [],
                        School::class => [],
                        Area::class => [],
                    ]);
                },
            ])
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return [
            'identity_type' => $identityType->value,
            'identity_type_label' => $identityType->getLabel(),
            'organization_type' => $organizationType,
            'items' => RcIdentityOrganizationItemResource::collection($identities)->resolve(),
        ];
    }
}
