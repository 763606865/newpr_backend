<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rc_payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->index()->comment('订单ID');
            $table->string('payment_no', 64)->unique()->comment('支付流水号');
            $table->tinyInteger('channel')->comment('支付渠道: 1-微信, 2-支付宝');
            $table->tinyInteger('status')->default(0)->comment('状态: 0-已初始化, 1-成功, 2-失败, 3-关闭');
            $table->decimal('amount', 10, 2)->comment('支付金额');
            $table->string('currency', 8)->default('CNY')->comment('币种');
            $table->string('provider_trade_no', 128)->nullable()->index()->comment('第三方交易号');
            $table->json('request_payload')->nullable()->comment('请求支付网关的参数');
            $table->json('response_payload')->nullable()->comment('支付网关返回参数');
            $table->dateTime('expired_at')->nullable()->comment('支付流水过期时间');
            $table->dateTime('paid_at')->nullable()->comment('支付成功时间');
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->unique(['channel', 'provider_trade_no']);
            $table->comment('RC支付流水表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_payment_transactions');
    }
};
