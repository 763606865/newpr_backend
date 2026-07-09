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
        Schema::create('rc_biz_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_name', 50)->comment('方案名称');
            $table->string('plan_code', 50)->comment('唯一标识')->unique();
            $table->decimal('price', 10, 2)->default(0)->comment('方案价格');
            $table->integer('duration')->default(0)->comment('方案时长(天) 0=永久');
            $table->tinyInteger('target_side')->default(1)->comment('目标端: 1-B端招聘方, 2-C端求职者');
            $table->tinyInteger('product_type')->default(1)->comment('商品类型: 1-职位发布, 2-会员套餐, 3-增值道具, 4-AI工具, 5-简历优化, 6-VIP辅导, 7-简历刷新, 8-简历曝光');
            $table->tinyInteger('billing_cycle')->default(1)->comment('计费周期: 1-一次性, 2-月, 3-季, 4-年, 5-按量');
            $table->integer('sort')->default(0)->comment('排序');
            $table->text('remark')->nullable()->comment('方案描述');
            $table->json('quota_rules')->nullable()->comment('配额规则定义');
            $table->tinyInteger('status')->default(1)->comment('0=禁用 1=启用');
            $table->mediumText('extra')->nullable()->comment('其他扩展属性');
            $table->timestamps();
            $table->comment('RC商业化方案表');
        });

        Schema::table('rc_jobs', function (Blueprint $table): void {
            $table->tinyInteger('is_paid_boost')->default(0)->after('status')->comment('是否商业化加速: 1-是, 0-否');
            $table->dateTime('boost_expires_at')->nullable()->after('is_paid_boost')->comment('加速权益过期时间');
        });

        // 将 VIP/增值权益放在独立表 rc_user_vips，按 user_id 绑定（避免 user_identity 冗余）
        Schema::create('rc_user_vips', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('关联 users.id');
            $table->tinyInteger('vip_level')->default(0)->comment('会员等级: 0-普通, 1-基础VIP, 2-高级VIP');
            $table->unsignedInteger('refresh_quota')->default(0)->comment('可用刷新次数');
            $table->unsignedInteger('exposure_quota')->default(0)->comment('可用曝光次数');
            $table->dateTime('last_refresh_at')->nullable()->comment('最近刷新时间');
            $table->unsignedBigInteger('plan_id')->nullable()->comment('关联的商业化方案 ID');
            $table->dateTime('expires_at')->nullable()->comment('权益到期时间');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();

            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->comment('用户 VIP/增值权益表，按 user_id 绑定');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_user_vips');

        Schema::table('rc_jobs', function (Blueprint $table): void {
            $table->dropColumn([
                'is_paid_boost',
                'boost_expires_at',
            ]);
        });

        Schema::dropIfExists('rc_biz_plans');
    }
};
