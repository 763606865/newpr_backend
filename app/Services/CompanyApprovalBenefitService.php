<?php

namespace App\Services;

use App\Enums\RcAssetCode;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyApprovalBenefitService extends Service
{
    public function grant(Company $company): void
    {
        DB::transaction(function () use ($company): void {
            $assetService = RcAssetService::make();

            $assetService->grantOnce(
                ownerType: RcAssetOwnerType::Company,
                ownerId: $company->id,
                assetCode: RcAssetCode::FullTimeJobPosting->value,
                assetName: (string) RcAssetCode::FullTimeJobPosting->getLabel(),
                quantity: 1,
                sourceType: RcAssetSourceType::System,
                sourceId: $company->id,
                bizNo: $this->bizNo($company, RcAssetCode::FullTimeJobPosting),
                remark: '企业审批通过赠送社招全职职位发布权益',
            );

            $assetService->grantOnce(
                ownerType: RcAssetOwnerType::Company,
                ownerId: $company->id,
                assetCode: RcAssetCode::CampusJobPosting->value,
                assetName: (string) RcAssetCode::CampusJobPosting->getLabel(),
                quantity: 10,
                sourceType: RcAssetSourceType::System,
                sourceId: $company->id,
                bizNo: $this->bizNo($company, RcAssetCode::CampusJobPosting),
                remark: '企业审批通过赠送校招职位发布权益',
            );
        });
    }

    private function bizNo(Company $company, RcAssetCode $assetCode): string
    {
        return sprintf('company_approval:%d:%s', $company->id, $assetCode->value);
    }
}
