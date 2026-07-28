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
        Schema::create('rc_resume_refresh_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->comment('求职者用户ID')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resume_id')->comment('简历ID')->constrained('rc_resumes')->cascadeOnDelete();
            $table->date('refresh_date')->comment('刷新日期');
            $table->dateTime('refreshed_at')->comment('实际刷新时间');
            $table->tinyInteger('trigger_type')->comment('触发类型: 1-更新简历, 2-主动刷新');
            $table->tinyInteger('quota_type')->comment('额度类型: 1-每日免费, 2-次数权益, 3-会员权益');
            $table->string('quota_key', 64)->nullable()->comment('并发幂等额度键');
            $table->foreignId('asset_ledger_id')->nullable()->unique()->comment('关联权益消费流水ID')->constrained('rc_asset_ledgers')->nullOnDelete();
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->unique(
                ['user_id', 'refresh_date', 'quota_key'],
                'rc_resume_refresh_logs_user_date_quota_unique',
            );
            $table->index(['resume_id', 'refreshed_at'], 'rc_resume_refresh_logs_resume_time_index');
            $table->comment('RC简历刷新记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_resume_refresh_logs');
    }
};
