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
        Schema::create('im_interaction_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id')->comment('IM 会话 ID');
            $table->unsignedBigInteger('sender_user_im_id')->comment('发起方 IM 用户 ID');
            $table->unsignedBigInteger('receiver_user_im_id')->comment('接收方 IM 用户 ID');
            $table->string('type', 64)->comment('交互请求类型');
            $table->string('status', 32)->default('pending')->comment('pending/accepted/rejected/expired/cancelled');
            $table->json('payload')->nullable()->comment('请求参数快照');
            $table->json('result_payload')->nullable()->comment('处理结果快照');
            $table->timestamp('responded_at')->nullable()->comment('响应时间');
            $table->timestamp('expires_at')->nullable()->comment('过期时间');
            $table->timestamps();

            $table->index(['conversation_id', 'status'], 'im_interaction_requests_conversation_status_idx');
            $table->index(['receiver_user_im_id', 'status'], 'im_interaction_requests_receiver_status_idx');
            $table->index(['sender_user_im_id', 'type'], 'im_interaction_requests_sender_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('im_interaction_requests');
    }
};
