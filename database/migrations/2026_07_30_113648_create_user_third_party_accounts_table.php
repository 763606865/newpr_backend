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
        Schema::create('user_third_party_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment('references users.id');
            $table->unsignedBigInteger('user_identity_id')->nullable()->comment('references rc_user_identities.id (optional)');
            $table->tinyInteger('identity_type')->nullable()->comment('身份类型: 1-求职者, 2-招聘方, 3-校招负责人, 4-政府机构负责人, 5-猎头');
            $table->string('provider', 64)->comment('三方登录渠道标识，如 wechat_mp/wechat_oa/wechat_open/ecosystem/wecom/alipay 等');
            $table->string('app_code')->nullable()->comment('渠道下的应用/站点标识，如微信 AppID、生态站点 code');
            $table->string('open_id')->nullable()->comment('第三方渠道返回的用户唯一标识（如微信 openid）');
            $table->string('union_id')->nullable()->comment('微信 unionid，用于同一开放平台账号下跨应用识别同一用户');
            $table->string('external_user_id')->nullable()->comment('生态站点登录场景下的外部用户标识（可选）');
            $table->json('extra')->nullable()->comment('扩展数据，如共享 token、第三方昵称/头像快照等');
            $table->timestamp('bound_at')->nullable()->comment('首次绑定/授权时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('user_identity_id');
            $table->unique(['provider', 'open_id']);
            $table->unique(['provider', 'union_id']);
            $table->unique(['provider', 'external_user_id']);
            $table->comment('用户三方登录绑定表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_third_party_accounts');
    }
};
