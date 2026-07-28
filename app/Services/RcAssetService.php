<?php

namespace App\Services;

use App\Enums\RcAssetChangeType;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Rc\AssetAccount;
use App\Models\Rc\AssetLedger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcAssetService extends Service
{
    /**
     * @param  array<string, mixed>|null  $extra
     */
    public function consumeOnce(
        RcAssetOwnerType $ownerType,
        int $ownerId,
        string $assetCode,
        string $assetName,
        int $quantity,
        RcAssetSourceType $sourceType,
        ?int $sourceId,
        string $bizNo,
        ?string $remark = null,
        ?array $extra = null,
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('权益消耗数量必须大于零。');
        }

        return DB::transaction(function () use (
            $ownerType,
            $ownerId,
            $assetCode,
            $assetName,
            $quantity,
            $sourceType,
            $sourceId,
            $bizNo,
            $remark,
            $extra,
        ): bool {
            $account = AssetAccount::query()
                ->where('owner_type', $ownerType->value)
                ->where('owner_id', $ownerId)
                ->where('asset_code', $assetCode)
                ->lockForUpdate()
                ->first();

            if (AssetLedger::query()->where('biz_no', $bizNo)->exists()) {
                return false;
            }

            if (
                $account === null
                || ($account->expired_at !== null && $account->expired_at->isPast())
                || $account->balance < $quantity
            ) {
                throw new InsufficientBalanceException($assetName.'权益不足。');
            }

            $account->balance -= $quantity;
            $account->save();

            AssetLedger::query()->create([
                'account_id' => $account->id,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'asset_code' => $assetCode,
                'change_type' => RcAssetChangeType::Consume,
                'delta' => -$quantity,
                'balance_after' => $account->balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'biz_no' => $bizNo,
                'happened_at' => now(),
                'remark' => $remark,
                'extra' => $extra,
            ]);

            return true;
        });
    }

    public function grantOnce(
        RcAssetOwnerType $ownerType,
        int $ownerId,
        string $assetCode,
        string $assetName,
        int $quantity,
        RcAssetSourceType $sourceType,
        ?int $sourceId,
        string $bizNo,
        ?string $remark = null,
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('权益发放数量必须大于零。');
        }

        return DB::transaction(function () use (
            $ownerType,
            $ownerId,
            $assetCode,
            $assetName,
            $quantity,
            $sourceType,
            $sourceId,
            $bizNo,
            $remark,
        ): bool {
            if (AssetLedger::query()->where('biz_no', $bizNo)->exists()) {
                return false;
            }

            $account = AssetAccount::query()
                ->where('owner_type', $ownerType->value)
                ->where('owner_id', $ownerId)
                ->where('asset_code', $assetCode)
                ->lockForUpdate()
                ->first();

            if ($account === null) {
                $account = AssetAccount::query()->create([
                    'owner_type' => $ownerType,
                    'owner_id' => $ownerId,
                    'asset_code' => $assetCode,
                    'asset_name' => $assetName,
                    'balance' => 0,
                    'frozen_balance' => 0,
                ]);
            }

            $account->balance += $quantity;
            $account->save();

            AssetLedger::query()->create([
                'account_id' => $account->id,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'asset_code' => $assetCode,
                'change_type' => RcAssetChangeType::Grant,
                'delta' => $quantity,
                'balance_after' => $account->balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'biz_no' => $bizNo,
                'happened_at' => now(),
                'remark' => $remark,
            ]);

            return true;
        });
    }
}
