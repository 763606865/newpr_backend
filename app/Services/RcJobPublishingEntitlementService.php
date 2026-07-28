<?php

namespace App\Services;

use App\Enums\RcAssetChangeType;
use App\Enums\RcAssetCode;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Enums\RcJobEmploymentType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Rc\AssetLedger;
use App\Models\Rc\Job;
use InvalidArgumentException;

class RcJobPublishingEntitlementService extends Service
{
    public function consumeFor(Job $job): bool
    {
        $preferredAssetCode = $this->resolveAssetCode($job->employment_type);
        $publishSequence = AssetLedger::query()
            ->where('source_type', RcAssetSourceType::System->value)
            ->where('source_id', $job->id)
            ->where('change_type', RcAssetChangeType::Consume->value)
            ->whereIn('asset_code', [
                RcAssetCode::JobPosting->value,
                RcAssetCode::FullTimeJobPosting->value,
                RcAssetCode::CampusJobPosting->value,
            ])
            ->count() + 1;

        foreach ([$preferredAssetCode, RcAssetCode::JobPosting] as $assetCode) {
            try {
                return RcAssetService::make()->consumeOnce(
                    ownerType: RcAssetOwnerType::Company,
                    ownerId: (int) $job->company_id,
                    assetCode: $assetCode->value,
                    assetName: (string) $assetCode->getLabel(),
                    quantity: 1,
                    sourceType: RcAssetSourceType::System,
                    sourceId: (int) $job->id,
                    bizNo: 'job_publish:'.$job->id.':'.$publishSequence,
                    remark: '发布职位：'.$job->title,
                    extra: [
                        'scene' => 'job_publish',
                        'job_id' => (int) $job->id,
                        'publish_sequence' => $publishSequence,
                        'employment_type' => $job->employment_type->value,
                        'preferred_asset_code' => $preferredAssetCode->value,
                    ],
                );
            } catch (InsufficientBalanceException $exception) {
                if ($assetCode === RcAssetCode::JobPosting) {
                    throw $exception;
                }
            }
        }

        return false;
    }

    private function resolveAssetCode(RcJobEmploymentType $employmentType): RcAssetCode
    {
        return match ($employmentType) {
            RcJobEmploymentType::FullTime => RcAssetCode::FullTimeJobPosting,
            RcJobEmploymentType::Campus => RcAssetCode::CampusJobPosting,
            default => throw new InvalidArgumentException(
                $employmentType->getLabel().'暂未配置职位发布权益。',
            ),
        };
    }
}
