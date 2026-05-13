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
        Schema::create('b_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable()->comment('真实姓名');
            $table->string('phone', 30)->unique();
            $table->string('email', 100)->nullable();
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('password')->nullable()->comment('密码');
            $table->string('status')->default('active')->comment('用户状态');
            $table->mediumText('extra')->nullable()->comment('扩展信息');
            $table->string('last_login_ip')->nullable()->comment('最后登录IP');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('company_b_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID');
            $table->unsignedBigInteger('b_user_id')->comment('用户ID');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_b_users');
        Schema::dropIfExists('b_users');
    }
};
