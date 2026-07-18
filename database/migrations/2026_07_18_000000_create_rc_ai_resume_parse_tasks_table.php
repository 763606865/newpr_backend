<?php

use App\Enums\RcAiResumeParseStatus;
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
        Schema::create('rc_ai_resume_parse_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment('发起用户ID');
            $table->unsignedBigInteger('identity_id')->comment('发起求职者身份ID，关联 rc_user_identities.id');
            $table->string('file_url', 2048)->comment('附件简历地址');
            $table->string('provider', 32)->nullable()->comment('AI 服务商标识');
            $table->unsignedTinyInteger('status')->default(RcAiResumeParseStatus::Pending->value)->comment('状态：0等待解析 1解析中 2解析成功 3解析失败');
            $table->json('parsed_resume')->nullable()->comment('解析后的简历信息');
            $table->text('error_message')->nullable()->comment('失败原因');
            $table->unsignedInteger('token_cost')->default(0)->comment('消耗 token 数，预留计费');
            $table->timestamp('started_at')->nullable()->comment('开始解析时间');
            $table->timestamp('finished_at')->nullable()->comment('完成解析时间');
            $table->timestamps();

            $table->index(['identity_id', 'status', 'created_at'], 'rc_ai_resume_parse_tasks_identity_status_idx');
            $table->index(['user_id', 'created_at'], 'rc_ai_resume_parse_tasks_user_created_idx');
            $table->comment('求职者 AI 简历解析任务表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_ai_resume_parse_tasks');
    }
};
