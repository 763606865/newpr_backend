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
        Schema::create('rc_resume_exposures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resume_id')->comment('关联简历ID')->constrained('rc_resumes')->cascadeOnDelete();
            $table->foreignId('user_id')->comment('关联求职者用户ID')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_ledger_id')->nullable()->unique()->comment('关联权益消费流水ID')->constrained('rc_asset_ledgers')->nullOnDelete();
            $table->dateTime('started_at')->comment('曝光开始时间');
            $table->dateTime('expired_at')->comment('曝光结束时间');
            $table->tinyInteger('status')->default(0)->comment('状态: 0-待生效, 1-生效中, 2-已过期, 3-已取消');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->index(['status', 'started_at', 'expired_at'], 'rc_resume_exposures_active_period_index');
            $table->index(['resume_id', 'status', 'expired_at'], 'rc_resume_exposures_resume_status_index');
            $table->comment('RC简历曝光记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_resume_exposures');
    }
};
