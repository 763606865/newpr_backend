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
        Schema::create('im_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('provider', 64)->nullable()->comment('IM 服务提供商标识');
            $table->string('app_code')->nullable()->comment('IM 应用编码');
            $table->string('conversation_no', 128)->unique()->comment('IM 平台返回的会话唯一标识');
            $table->string('conversation_type', 32)->nullable()->comment('single/group/chatroom/live_room');
            $table->string('conversation_key', 128)->nullable()->unique()->comment('业务侧确定性会话去重键，单聊按成员集合生成');

            $table->morphs('owner', 'im_conversations_owner_index');

            $table->string('scene', 64)->nullable()->comment('业务场景标识');
            $table->json('metadata')->nullable()->comment('可选元数据（json）');
            $table->timestamp('last_message_at')->nullable()->comment('最后消息时间');
            $table->timestamp('expires_at')->nullable()->comment('可选过期时间，便于清理临时会话');
            $table->timestamps();

            $table->index('last_message_at', 'im_conversations_last_message_idx');
        });

        Schema::create('im_conversation_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id')->comment('references im_conversations.id');
            $table->morphs('member', 'im_conversation_members_member_index');
            $table->string('role', 32)->nullable()->comment('成员角色');
            $table->timestamp('joined_at')->nullable()->comment('加入时间');
            $table->timestamp('last_read_at')->nullable()->comment('最后已读时间');
            $table->json('settings')->nullable()->comment('成员级配置，如免打扰/置顶');
            $table->timestamps();
            $table->unique(['conversation_id', 'member_type', 'member_id'], 'im_conversation_members_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('im_conversation_members');
        Schema::dropIfExists('im_conversations');
    }
};
