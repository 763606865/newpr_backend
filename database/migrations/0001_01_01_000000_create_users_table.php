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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->binary('uuid', 16)->unique()->comment('全局唯一用户标识(UUID v4/v7)');
            $table->string('name', 50)->nullable()->comment('真实姓名');
            $table->string('nickname', 50)->nullable()->comment('昵称');
            $table->string('phone', 30)->unique();
            $table->string('email', 100);
            $table->string('avatar')->comment('头像');
            $table->tinyInteger('gender')->nullable()->default(0)->comment('性别');
            $table->string('password')->nullable()->comment('密码');
            $table->string('status')->default('active')->comment('用户状态');
            $table->string('wechat_unionid', 64)->nullable()->unique()->comment('微信UnionID');
            $table->mediumText('extra')->nullable()->comment('扩展信息');
            $table->string('last_login_ip')->nullable()->comment('最后登录IP');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
