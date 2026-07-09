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
        Schema::create('rc_asset_accounts', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('owner_type')->comment('资产主体类型: 1-企业, 2-个人');
            $table->unsignedBigInteger('owner_id')->comment('资产主体ID');
            $table->string('asset_code', 64)->comment('资产编码');
            $table->string('asset_name', 100)->comment('资产名称');
            $table->bigInteger('balance')->default(0)->comment('可用余额');
            $table->bigInteger('frozen_balance')->default(0)->comment('冻结余额');
            $table->dateTime('expired_at')->nullable()->comment('权益到期时间');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id', 'asset_code'], 'rc_asset_accounts_owner_asset_unique');
            $table->index(['asset_code', 'expired_at']);
            $table->comment('RC商业化资产账户表');
        });

        Schema::create('rc_asset_ledgers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id')->comment('资产账户ID')->index();
            $table->tinyInteger('owner_type')->comment('资产主体类型: 1-企业, 2-个人');
            $table->unsignedBigInteger('owner_id')->comment('资产主体ID');
            $table->string('asset_code', 64)->comment('资产编码');
            $table->tinyInteger('change_type')->comment('变动类型: 1-发放, 2-消耗, 3-退款, 4-过期, 5-人工调整');
            $table->bigInteger('delta')->comment('变动值(可正可负)');
            $table->bigInteger('balance_after')->comment('变动后余额');
            $table->tinyInteger('source_type')->default(0)->comment('来源类型: 0-未知, 1-订单, 2-系统, 3-人工');
            $table->unsignedBigInteger('source_id')->nullable()->comment('来源ID');
            $table->string('biz_no', 64)->nullable()->comment('业务流水号');
            $table->dateTime('happened_at')->nullable()->comment('发生时间');
            $table->string('remark')->nullable()->comment('备注');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->index(['owner_type', 'owner_id', 'asset_code'], 'rc_asset_ledgers_owner_asset_index');
            $table->index(['source_type', 'source_id']);
            $table->comment('RC商业化资产流水表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_asset_ledgers');
        Schema::dropIfExists('rc_asset_accounts');
    }
};
