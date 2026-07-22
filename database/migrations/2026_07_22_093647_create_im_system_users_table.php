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
        Schema::create('im_system_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 64)->comment('系统用户编码，如 rc_notice');
            $table->string('name', 64)->comment('系统用户展示名称');
            $table->string('provider', 64)->comment('IM 服务提供商标识');
            $table->string('app_code')->default('')->comment('IM 应用编码');
            $table->string('external_user_id', 128)->comment('业务侧传给 IM 的系统用户 ID');
            $table->string('im_user_id', 128)->nullable()->comment('IM 平台返回的系统用户 ID');
            $table->string('avatar')->nullable()->comment('系统用户头像');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->json('extra')->nullable()->comment('供应商扩展数据');
            $table->timestamps();

            $table->unique(['provider', 'app_code', 'code'], 'im_system_users_provider_app_code_unique');
            $table->unique(['provider', 'external_user_id'], 'im_system_users_provider_external_unique');
            $table->unique(['provider', 'im_user_id'], 'im_system_users_provider_im_user_unique');
            $table->index('is_active', 'im_system_users_is_active_idx');
            $table->comment('IM系统用户表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('im_system_users');
    }
};
