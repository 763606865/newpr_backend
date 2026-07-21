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
        Schema::create('rc_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('举报用户ID')->index();
            $table->unsignedBigInteger('creator_user_identity_id')->comment('身份ID')->index();
            $table->morphs('reportable');
            $table->tinyInteger('reason_type')->comment('举报原因类型: 1-虚假信息, 2-诈骗或收费, 3-违法违规内容, 4-骚扰或不当联系, 99-其他')->index();
            $table->string('reason', 100)->nullable()->comment('举报原因');
            $table->text('description')->nullable()->comment('举报说明');
            $table->json('attachments')->nullable()->comment('举报凭证附件');
            $table->tinyInteger('status')->default(0)->comment('处理状态: 0-待处理, 1-处理中, 2-已处理, 3-已驳回')->index();
            $table->unsignedBigInteger('handler_admin_user_id')->nullable()->comment('处理管理员ID')->index();
            $table->text('handle_result')->nullable()->comment('处理结果');
            $table->timestamp('handled_at')->nullable()->comment('处理时间')->index();
            $table->string('ip', 45)->nullable()->comment('举报IP');
            $table->text('user_agent')->nullable()->comment('User-Agent');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'reportable_type', 'reportable_id'], 'rc_reports_user_reportable_index');
            $table->index(['status', 'created_at'], 'rc_reports_status_created_at_index');
            $table->comment('招聘举报表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_reports');
    }
};
