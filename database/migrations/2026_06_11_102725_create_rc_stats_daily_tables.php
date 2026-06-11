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
        Schema::create('rc_job_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID（冗余，便于招聘方看板聚合）')->index();
            $table->unsignedBigInteger('user_id')->nullable()->comment('职位发布人用户ID')->index();
            $table->unsignedBigInteger('job_id')->comment('职位ID')->index();
            $table->date('stat_date')->comment('统计日期')->index();
            $table->unsignedInteger('views_total')->default(0)->comment('浏览量（PV）');
            $table->unsignedInteger('views_uv')->default(0)->comment('独立访客数（UV）');
            $table->timestamps();

            $table->unique(['job_id', 'stat_date'], name: 'rc_job_stats_daily_job_id_stat_date_unique');
            $table->index(['user_id', 'stat_date'], name: 'rc_job_stats_daily_user_id_stat_date_index');
            $table->index(['company_id', 'stat_date'], name: 'rc_job_stats_daily_company_id_stat_date_index');
            $table->comment('招聘职位浏览量日统计表');
        });

        Schema::create('rc_resume_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID（冗余，便于求职者看板聚合）')->index();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->date('stat_date')->comment('统计日期')->index();
            $table->unsignedInteger('views_total')->default(0)->comment('浏览量（PV）');
            $table->unsignedInteger('views_uv')->default(0)->comment('独立访客数（UV）');
            $table->timestamps();

            $table->unique(['resume_id', 'stat_date'], name: 'rc_resume_stats_daily_resume_id_stat_date_unique');
            $table->index(['user_id', 'stat_date'], name: 'rc_resume_stats_daily_user_id_stat_date_index');
            $table->comment('招聘简历浏览量日统计表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_resume_stats_daily');
        Schema::dropIfExists('rc_job_stats_daily');
    }
};
