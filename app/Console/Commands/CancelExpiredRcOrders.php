<?php

namespace App\Console\Commands;

use App\Services\RcOrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rc:orders:cancel-expired')]
#[Description('取消超过五分钟仍未支付的 RC 商品订单')]
class CancelExpiredRcOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = RcOrderService::make()->cancelExpiredPendingOrders();
        $this->info("已取消 {$count} 个超时待支付订单。");

        return self::SUCCESS;
    }
}
