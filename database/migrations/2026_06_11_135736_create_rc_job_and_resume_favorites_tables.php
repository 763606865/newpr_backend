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
        Schema::create('rc_job_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->unsignedBigInteger('job_id')->comment('职位ID')->index();
            $table->timestamps();

            $table->unique(['user_id', 'job_id'], 'rc_job_favorites_user_job_unique');
        });

        Schema::create('rc_resume_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('招聘方用户ID')->index();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->timestamps();

            $table->unique(
                ['user_id', 'company_id', 'resume_id'],
                'rc_resume_favorites_user_company_resume_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_resume_favorites');
        Schema::dropIfExists('rc_job_favorites');
    }
};
