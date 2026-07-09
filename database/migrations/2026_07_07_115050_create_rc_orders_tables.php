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
        Schema::create('rc_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_no', 64)->comment('订单号')->unique();
            $table->tinyInteger('payer_type')->comment('付款主体类型: 1-企业, 2-个人');
            $table->unsignedBigInteger('payer_id')->comment('付款主体ID');
            $table->unsignedBigInteger('buyer_user_id')->nullable()->comment('购买操作人用户ID');
            $table->tinyInteger('scene_type')->comment('场景类型: 1-B端套餐, 2-B端道具, 3-B端AI工具, 11-C端VIP, 12-C端简历优化, 13-C端刷新, 14-C端曝光');
            $table->string('product_code', 64)->comment('商品编码');
            $table->string('product_name', 100)->comment('商品名称');
            $table->unsignedInteger('quantity')->default(1)->comment('购买数量');
            $table->decimal('original_amount', 10, 2)->default(0)->comment('原始金额');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('优惠金额');
            $table->decimal('payable_amount', 10, 2)->default(0)->comment('应付金额');
            $table->decimal('paid_amount', 10, 2)->default(0)->comment('实付金额');
            $table->string('currency', 8)->default('CNY')->comment('币种');
            $table->tinyInteger('pay_channel')->default(0)->comment('支付渠道: 0-未支付, 1-微信, 2-支付宝, 3-银行卡, 4-线下');
            $table->tinyInteger('pay_status')->default(0)->comment('支付状态: 0-待支付, 1-已支付, 2-支付失败, 3-已退款');
            $table->tinyInteger('order_status')->default(0)->comment('订单状态: 0-待支付, 1-已完成, 2-已取消, 3-已关闭');
            $table->dateTime('expired_at')->nullable()->comment('过期时间');
            $table->dateTime('paid_at')->nullable()->comment('支付时间');
            $table->dateTime('canceled_at')->nullable()->comment('取消时间');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['payer_type', 'payer_id']);
            $table->index(['buyer_user_id', 'created_at']);
            $table->index(['scene_type', 'order_status']);
            $table->comment('RC商业化订单表');
        });

        Schema::create('rc_order_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->index()->comment('订单ID');
            $table->string('item_code', 64)->comment('条目编码');
            $table->string('item_name', 100)->comment('条目名称');
            $table->tinyInteger('item_type')->comment('条目类型: 1-套餐, 2-道具, 3-AI服务, 4-VIP服务');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('单价');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->decimal('line_amount', 10, 2)->default(0)->comment('行金额');
            $table->json('entitlement_snapshot')->nullable()->comment('权益快照');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->comment('RC商业化订单明细表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_order_items');
        Schema::dropIfExists('rc_orders');
    }
};
