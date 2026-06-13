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
        Schema::create('rc_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('接收用户ID')->index();
            $table->unsignedBigInteger('user_identity_id')->nullable()->comment('接收身份ID')->index();
            $table->tinyInteger('type')->comment('通知类型')->index();
            $table->string('title')->comment('通知标题');
            $table->string('body')->nullable()->comment('通知摘要');
            $table->json('payload')->nullable()->comment('业务扩展数据');
            $table->timestamp('read_at')->nullable()->comment('已读时间')->index();
            $table->timestamp('happened_at')->nullable()->comment('事件发生时间')->index();
            $table->timestamps();
            $table->index(['user_id', 'user_identity_id', 'read_at']);
            $table->index(['user_id', 'type']);
            $table->comment('招聘站内通知表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_notifications');
    }
};
