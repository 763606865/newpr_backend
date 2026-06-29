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
        Schema::create('rc_announcements', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('organization', 'rc_announcements_organization_index');
            $table->string('publisher_name')->nullable()->comment('发布人名称');
            $table->unsignedTinyInteger('publisher_type')->default(0)->comment('发布人类型，见 CmsAnnouncementPublisherType');
            $table->string('title')->comment('公告标题');
            $table->string('sub_title')->nullable()->comment('公告副标题');
            $table->string('cover')->nullable()->comment('推广图');
            $table->string('summary', 1000)->nullable()->comment('公告摘要');
            $table->longText('content')->nullable()->comment('公告正文');
            $table->string('link_url')->nullable()->comment('官网外链地址（主跳转）');
            $table->json('employment_types')->nullable()->comment('工作类型列表，见 RcJobEmploymentType，如 [3,4] 表示实习+校招');
            $table->unsignedTinyInteger('education_level')->nullable()->comment('最低学历要求，见 RcEducationLevel');
            $table->json('graduation_years')->nullable()->comment('面向毕业年份，如 [2026,2027]');
            $table->text('major_requirement')->nullable()->comment('专业要求说明（展示用）');
            $table->unsignedTinyInteger('is_nationwide')->default(0)->comment('是否全国招聘: 1-是, 0-否');
            $table->timestamp('apply_start_at')->nullable()->comment('报名开始时间')->index();
            $table->timestamp('apply_end_at')->nullable()->comment('报名截止时间')->index();
            $table->unsignedTinyInteger('apply_deadline_type')->default(1)->comment('截止类型: 1-指定日期, 2-招满即止');
            $table->timestamp('published_at')->nullable()->comment('发布时间')->index();
            $table->timestamp('expired_at')->nullable()->comment('失效时间')->index();
            $table->unsignedTinyInteger('is_top')->default(0)->comment('是否置顶: 1-是, 0-否');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态: 1-草稿, 2-已发布, 3-下线');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('source_name')->nullable()->comment('来源名称');
            $table->string('source_url')->nullable()->comment('来源地址');
            $table->unsignedInteger('read_count')->default(0)->comment('阅读人数');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();

            $table->index('publisher_type');
            $table->index('education_level');
            $table->index('status');
            $table->comment('招聘公告表');
        });

        Schema::create('rc_announcement_cities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('announcement_id')->comment('公告ID')->index();
            $table->string('city_code', 32)->comment('工作城市编码')->index();
            $table->timestamps();

            $table->unique(['announcement_id', 'city_code'], 'rc_announcement_cities_unique');
            $table->comment('招聘公告工作城市关联表');
        });

        Schema::create('rc_announcement_majors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('announcement_id')->comment('公告ID')->index();
            $table->string('major_code', 30)->comment('专业国标编码，关联 majors.full_code')->index();
            $table->timestamps();

            $table->unique(['announcement_id', 'major_code'], 'rc_announcement_majors_unique');
            $table->comment('招聘公告专业关联表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_announcement_majors');
        Schema::dropIfExists('rc_announcement_cities');
        Schema::dropIfExists('rc_announcements');
    }
};
