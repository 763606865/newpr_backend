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
        Schema::create('rc_resume_exposure_stats_daily', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exposure_id')->comment('简历曝光记录ID')->constrained('rc_resume_exposures')->cascadeOnDelete();
            $table->foreignId('resume_id')->comment('简历ID')->constrained('rc_resumes')->cascadeOnDelete();
            $table->foreignId('company_id')->comment('看到曝光简历的企业ID')->constrained('companies')->cascadeOnDelete();
            $table->date('stat_date')->comment('统计日期');
            $table->unsignedInteger('impressions')->default(0)->comment('曝光展示次数');
            $table->unsignedInteger('detail_views')->default(0)->comment('曝光带来的详情浏览次数');
            $table->unsignedInteger('contacts')->default(0)->comment('曝光带来的沟通次数');
            $table->unsignedInteger('favorites')->default(0)->comment('曝光带来的收藏次数');
            $table->unsignedInteger('invitations')->default(0)->comment('曝光带来的邀请次数');
            $table->timestamps();
            $table->unique(
                ['exposure_id', 'company_id', 'stat_date'],
                'rc_resume_exposure_stats_exposure_company_date_unique',
            );
            $table->index(['resume_id', 'stat_date'], 'rc_resume_exposure_stats_resume_date_index');
            $table->index(['company_id', 'stat_date'], 'rc_resume_exposure_stats_company_date_index');
            $table->comment('RC简历曝光效果日统计表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_resume_exposure_stats_daily');
    }
};
