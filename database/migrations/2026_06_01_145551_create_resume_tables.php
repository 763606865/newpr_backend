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
        // 常见职位表
        Schema::create('rc_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('职位名称');
            $table->string('code')->comment('职位代码');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父级职位ID');
            $table->integer('sort')->default(0)->comment('排序');
            $table->mediumText('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique('code');
            $table->index('parent_id');
            $table->index('sort');
            $table->comment('常见职位表');
        });
        // 常见行业
        Schema::create('rc_industries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('行业名称');
            $table->string('code')->comment('行业代码');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父级行业ID');
            $table->integer('sort')->default(0)->comment('排序');
            $table->mediumText('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique('code');
            $table->index('parent_id');
            $table->index('sort');
            $table->comment('常见行业表');
        });
        // 求职意向
        Schema::create('rc_resume_intentions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->tinyInteger('job_status')->default(1)->comment('求职状态: 1-在职考虑机会, 2-在职不考虑, 3-离职找工作, 4-应届生');
            $table->tinyInteger('employment_type')->nullable()->comment('工作类型: 1-全职, 2-兼职, 3-实习');
            $table->string('expected_city_code', 32)->nullable()->comment('期望城市代码(单选)')->index();
            $table->json('expected_industry_codes')->nullable()->comment('期望行业代码列表(多选)');
            $table->unsignedBigInteger('expected_position_id')->nullable()->comment('期望职位ID(单选)')->index();
            $table->decimal('salary_min', 10, 2)->nullable()->comment('最低期望薪资');
            $table->decimal('salary_max', 10, 2)->nullable()->comment('最高期望薪资');
            $table->tinyInteger('salary_unit')->default(1)->comment('薪资单位: 1-月, 2-日, 3-时');
            $table->date('available_date')->nullable()->comment('可到岗日期');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('简历求职意向表');
        });

        // 工作/实习经历
        Schema::create('rc_resume_works', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('company_name')->comment('公司名称');
            $table->string('department')->nullable()->comment('部门');
            $table->string('position')->comment('职位');
            $table->tinyInteger('employment_type')->default(1)->comment('工作类型: 1-全职, 2-兼职, 3-实习');
            $table->date('start_date')->comment('开始时间');
            $table->date('end_date')->nullable()->comment('结束时间，null表示至今');
            $table->tinyInteger('is_current')->default(0)->comment('是否在职: 1-是, 0-否');
            $table->text('description')->nullable()->comment('工作描述');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历工作/实习经历表');
        });

        // 教育经历
        Schema::create('rc_resume_educations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('school_name')->comment('学校名称');
            $table->string('major')->nullable()->comment('专业');
            $table->tinyInteger('degree')->nullable()->comment('学历: 1-高中/中专, 2-专科, 3-本科, 4-硕士, 5-博士, 6-其他');
            $table->tinyInteger('education_type')->default(1)->comment('学习方式: 1-全日制, 2-非全日制');
            $table->date('start_date')->comment('开始时间');
            $table->date('end_date')->nullable()->comment('结束时间，null表示至今');
            $table->tinyInteger('is_current')->default(0)->comment('是否在读: 1-是, 0-否');
            $table->text('description')->nullable()->comment('在校描述');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历教育经历表');
        });

        // 项目经历
        Schema::create('rc_resume_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('project_name')->comment('项目名称');
            $table->string('role')->nullable()->comment('担任角色');
            $table->string('company_name')->nullable()->comment('所在公司/机构');
            $table->date('start_date')->comment('开始时间');
            $table->date('end_date')->nullable()->comment('结束时间，null表示至今');
            $table->tinyInteger('is_current')->default(0)->comment('是否进行中: 1-是, 0-否');
            $table->text('description')->nullable()->comment('项目描述');
            $table->text('achievement')->nullable()->comment('项目成果');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历项目经历表');
        });

        // 培训经历
        Schema::create('rc_resume_trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('institution_name')->comment('培训机构名称');
            $table->string('course_name')->comment('培训课程名称');
            $table->date('start_date')->comment('开始时间');
            $table->date('end_date')->nullable()->comment('结束时间');
            $table->text('description')->nullable()->comment('培训描述');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历培训经历表');
        });

        // 语言能力
        Schema::create('rc_resume_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('language', 50)->comment('语言名称');
            $table->tinyInteger('proficiency')->default(2)->comment('熟练程度: 1-入门, 2-日常交流, 3-商务谈判, 4-精通');
            $table->string('certificate', 100)->nullable()->comment('相关证书名称');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历语言能力表');
        });

        // 专业技能
        Schema::create('rc_resume_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('skill_name', 100)->comment('技能名称');
            $table->tinyInteger('proficiency')->default(2)->comment('熟练程度: 1-了解, 2-熟悉, 3-熟练, 4-精通');
            $table->string('description')->nullable()->comment('技能描述');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历专业技能表');
        });

        // 证书/荣誉
        Schema::create('rc_resume_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('name')->comment('证书/荣誉名称');
            $table->tinyInteger('cert_type')->default(1)->comment('类型: 1-证书, 2-荣誉奖项');
            $table->string('issuer')->nullable()->comment('颁发机构');
            $table->date('issue_date')->nullable()->comment('获得日期');
            $table->date('expire_date')->nullable()->comment('到期日期，null表示长期有效');
            $table->string('cert_no', 100)->nullable()->comment('证书编号');
            $table->text('description')->nullable()->comment('描述');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历证书/荣誉表');
        });

        // 个人作品
        Schema::create('rc_resume_portfolios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('user_id')->comment('用户ID')->index();
            $table->string('title')->comment('作品标题');
            $table->tinyInteger('type')->default(1)->comment('类型: 1-链接, 2-图片, 3-视频, 4-文档, 5-其他');
            $table->string('url')->nullable()->comment('作品链接/文件地址');
            $table->string('cover_url')->nullable()->comment('封面图地址');
            $table->text('description')->nullable()->comment('作品描述');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resume_id', 'sort']);
            $table->comment('简历个人作品表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_resume_portfolios');
        Schema::dropIfExists('rc_resume_certificates');
        Schema::dropIfExists('rc_resume_skills');
        Schema::dropIfExists('rc_resume_languages');
        Schema::dropIfExists('rc_resume_trainings');
        Schema::dropIfExists('rc_resume_projects');
        Schema::dropIfExists('rc_resume_educations');
        Schema::dropIfExists('rc_resume_works');
        Schema::dropIfExists('rc_resume_intentions');
        Schema::dropIfExists('rc_industries');
        Schema::dropIfExists('rc_positions');
    }
};
