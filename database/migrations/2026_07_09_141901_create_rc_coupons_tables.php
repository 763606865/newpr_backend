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
        Schema::create('rc_coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique()->comment('券码');
            $table->string('title', 100)->nullable()->comment('券名称');
            $table->unsignedBigInteger('plan_id')->nullable()->comment('可绑定的 plan');
            $table->string('discount_type', 20)->default('fixed')->comment('fixed|percent|quota');
            $table->decimal('discount_value', 10, 2)->nullable()->comment('折扣或配额值');
            $table->integer('usage_limit')->default(1)->comment('每人可用次数');
            $table->integer('total_quantity')->nullable()->comment('总发行量');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->tinyInteger('status')->default(1)->comment('0=禁用,1=启用');
            $table->json('extra')->nullable()->comment('扩展数据');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('优惠券表');
        });

        Schema::create('rc_coupon_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('coupon_id')->index()->comment('关联券');
            $table->unsignedBigInteger('user_id')->index()->comment('使用者');
            $table->unsignedBigInteger('order_id')->nullable()->comment('相关订单');
            $table->dateTime('used_at')->nullable();
            $table->json('extra')->nullable()->comment('扩展数据');
            $table->timestamps();
            $table->comment('优惠券使用记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_coupon_redemptions');
        Schema::dropIfExists('rc_coupons');
    }
};
