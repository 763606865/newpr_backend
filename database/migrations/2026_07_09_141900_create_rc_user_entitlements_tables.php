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
        Schema::create('rc_user_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('关联 users.id');
            $table->string('entitlement_type', 50)->comment('类型: refresh|exposure|other');
            $table->integer('quantity')->default(0)->comment('发放数量');
            $table->integer('remaining')->default(0)->comment('剩余可用数量');
            $table->unsignedBigInteger('plan_id')->nullable()->comment('关联的商业化方案 id');
            $table->string('source', 50)->nullable()->comment('来源: order|coupon|admin');
            $table->unsignedBigInteger('source_id')->nullable()->comment('来源记录 id');
            $table->dateTime('expires_at')->nullable()->comment('到期时间');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();

            $table->index(['user_id', 'entitlement_type']);
            $table->comment('用户配额表');
        });

        Schema::create('rc_user_entitlement_usages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entitlement_id')->index()->comment('关联 rc_user_entitlements.id')->index();
            $table->unsignedBigInteger('user_id')->index()->comment('冗余 user_id，便于查询')->index();
            $table->string('action', 50)->comment('消费动作: use|revoke|grant');
            $table->integer('delta')->comment('变更量，通常为 -1|+N');
            $table->integer('balance_after')->nullable()->comment('变更后剩余数');
            $table->unsignedBigInteger('related_order_id')->nullable()->comment('相关订单 id');
            $table->json('extra')->nullable()->comment('扩展信息');
            $table->timestamps();

            $table->comment('用户用量记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_user_entitlement_usages');
        Schema::dropIfExists('rc_user_entitlements');
    }
};
