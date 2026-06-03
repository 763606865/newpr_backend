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
        Schema::create('rc_user_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('关联用户ID')->index();
            $table->unsignedBigInteger('company_id')->nullable()->comment('所属企业ID')->index();
            $table->tinyInteger('identity_type')->comment('身份类型: 1-求职者, 2-招聘方, 3-校招负责人, 4-政府机构负责人, 5-猎头')->index();
            $table->string('identity_name', 50)->comment('身份名称');
            $table->string('organization_name')->nullable()->comment('所属机构名称');
            $table->string('job_title')->nullable()->comment('头衔/岗位名称');
            $table->tinyInteger('is_default')->default(0)->comment('是否默认身份: 1-是, 0-否');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'identity_type']);
            $table->index(['company_id', 'identity_type']);
            $table->comment('招聘用户身份表');
        });

        Schema::create('rc_resumes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('关联用户ID')->index();
            $table->string('resume_no', 64)->comment('简历编号');
            $table->string('title')->comment('简历名称');

            // 基础个人信息
            $table->string('full_name', 50)->index()->comment('姓名');
            $table->tinyInteger('gender')->default(0)->comment('性别');
            $table->char('id_card', 18)->nullable()->comment('身份证号');
            $table->string('nation', 20)->default('汉族')->comment('民族');
            $table->string('avatar')->nullable()->comment('头像');
            // 出生信息
            $table->date('birth_date')->nullable()->comment('完整出生日期（格式：YYYY-MM-DD）');
            $table->char('birth_month', 7)->nullable()->comment('出生年月（格式：YYYY-MM）');
            $table->unsignedTinyInteger('age')->nullable()->comment('年龄');
            // 身份与婚姻信息
            $table->tinyInteger('marital_status')->default(0)->comment('婚姻状况:0-未知，1-未婚，2-已婚，3-离异，4-丧偶');
            $table->string('political_status', 20)->default('群众')->comment('政治面貌');
            $table->string('native_place', 100)->nullable()->comment('籍贯（完整省市区）');
            $table->tinyInteger('current_identity')->default(0)->comment('当前身份:职场人, 学生，待业，0-其他');
            // 工作信息
            $table->date('work_start_date')->nullable()->comment('参加工作日期（用于计算工作年限）');
            $table->unsignedTinyInteger('work_years')->nullable()->comment('工作年限（年）');
            $table->string('current_salary', 50)->nullable()->comment('当前/期望薪资');
            $table->string('salary_remark', 200)->nullable()->comment('薪资备注（如20K·14薪、税前/税后等）');
            $table->string('recruit_source', 100)->nullable()->comment('招聘信息获取来源');
            // 筛选冗余字段（用于提升简历列表检索效率）
            $table->tinyInteger('highest_education_level')->nullable()->comment('最高学历: 1-高中/中专, 2-专科, 3-本科, 4-硕士, 5-博士, 6-其他');
            $table->tinyInteger('is_fresh_graduate')->default(0)->comment('是否应届生: 1-是, 0-否');
            $table->decimal('expected_salary_min', 10, 2)->nullable()->comment('期望薪资下限');
            $table->decimal('expected_salary_max', 10, 2)->nullable()->comment('期望薪资上限');
            $table->tinyInteger('expected_salary_unit')->default(1)->comment('期望薪资单位: 1-月, 2-日, 3-时');
            // 地址信息
            $table->string('household_register', 100)->nullable()->comment('户口所在地（精简版，如省-市）');
            $table->string('household_register_detail', 200)->nullable()->comment('户口所在地详细地址（省-市-区县）');
            $table->string('current_residence_city', 100)->nullable()->comment('现居住城市（省-市-区县）');
            $table->string('current_city_code', 32)->nullable()->comment('现居住城市编码');
            $table->string('current_residence_detail', 200)->nullable()->comment('现居住地详细地址');
            $table->string('residence_country', 50)->default('中国')->comment('现居住国家/地区');
            // 联系方式
            $table->string('phone', 20)->index()->comment('联系电话');
            $table->string('email', 100)->index()->comment('电子邮箱');
            $table->tinyInteger('source_type')->default(1)->comment('来源类型: 1-上传, 2-解析, 3-手工创建, 4-导入');
            $table->string('file_url')->nullable()->comment('简历文件地址');
            $table->string('file_name')->nullable()->comment('简历文件名称');
            $table->string('file_ext', 16)->nullable()->comment('文件后缀');
            $table->longText('text_content')->nullable()->comment('简历文本内容');
            $table->json('parsed_data')->nullable()->comment('解析后的结构化数据');
            $table->tinyInteger('is_primary')->default(0)->comment('是否主简历: 1-是, 0-否');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-正常, 0-停用');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index('id_card');
            $table->index(['user_id', 'is_primary']);
            $table->index(['user_id', 'status']);
            $table->index('age');
            $table->index('work_years');
            $table->index('highest_education_level');
            $table->index('is_fresh_graduate');
            $table->index(['expected_salary_min', 'expected_salary_max']);
            $table->index('current_city_code');
            $table->unique(['user_id', 'resume_no'], name: 'rc_resumes_user_id_resume_no_unique');
            $table->comment('招聘简历表');
        });

        Schema::create('rc_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('department_id')->nullable()->comment('部门ID')->index();
            $table->unsignedBigInteger('creator_user_id')->nullable()->comment('创建人用户ID')->index();
            $table->string('code', 64)->comment('职位编码');
            $table->string('title')->comment('职位名称');
            $table->tinyInteger('employment_type')->default(1)->comment('用工类型: 1-全职, 2-兼职, 3-实习, 4-校招, 5-外包');
            $table->string('city_code', 32)->nullable()->comment('工作城市编码')->index();
            $table->string('workplace')->nullable()->comment('工作地点');
            $table->decimal('salary_min', 10, 2)->nullable()->comment('最低薪资');
            $table->decimal('salary_max', 10, 2)->nullable()->comment('最高薪资');
            $table->tinyInteger('salary_unit')->default(1)->comment('薪资单位: 1-月, 2-日, 3-时');
            $table->tinyInteger('experience_min')->nullable()->comment('最低经验年限');
            $table->tinyInteger('experience_max')->nullable()->comment('最高经验年限');
            $table->tinyInteger('education_level')->nullable()->comment('最低学历要求');
            $table->integer('headcount')->default(1)->comment('招聘人数');
            $table->text('description')->nullable()->comment('职位描述');
            $table->text('requirement')->nullable()->comment('职位要求');
            $table->text('benefit')->nullable()->comment('福利待遇');
            $table->tinyInteger('status')->default(0)->comment('状态: 0-草稿, 1-已发布, 2-暂停, 3-关闭, 4-过期');
            $table->timestamp('published_at')->nullable()->comment('发布时间')->index();
            $table->timestamp('expired_at')->nullable()->comment('过期时间')->index();
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code'], name: 'rc_jobs_company_id_code_unique');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'city_code']);
            $table->comment('招聘职位表');
        });

        Schema::create('rc_job_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->string('code', 64)->comment('阶段编码');
            $table->string('name')->comment('阶段名称');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('is_default')->default(0)->comment('是否默认阶段: 1-是, 0-否');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code'], name: 'rc_job_stages_company_id_code_unique');
            $table->comment('招聘流程阶段表');
        });

        Schema::create('rc_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('job_id')->comment('职位ID')->index();
            $table->unsignedBigInteger('candidate_user_id')->comment('候选人用户ID')->index();
            $table->unsignedBigInteger('resume_id')->comment('简历ID')->index();
            $table->unsignedBigInteger('current_stage_id')->nullable()->comment('当前阶段ID')->index();
            $table->tinyInteger('source_type')->default(1)->comment('来源类型: 1-主动投递, 2-内推, 3-猎头, 4-校招, 5-政府渠道, 6-导入');
            $table->tinyInteger('status')->default(0)->comment('投递状态: 0-待处理, 1-筛选中, 2-面试中, 3-Offer中, 4-录用, 5-淘汰, 6-撤回');
            $table->timestamp('applied_at')->nullable()->comment('投递时间')->index();
            $table->timestamp('withdrawn_at')->nullable()->comment('撤回时间');
            $table->json('resume_snapshot')->nullable()->comment('投递时简历快照');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'job_id', 'candidate_user_id'], name: 'rc_applications_company_id_job_id_candidate_user_id_unique');
            $table->index(['company_id', 'status']);
            $table->comment('招聘投递表');
        });

        Schema::create('rc_application_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('application_id')->comment('投递ID')->index();
            $table->unsignedBigInteger('from_stage_id')->nullable()->comment('原阶段ID')->index();
            $table->unsignedBigInteger('to_stage_id')->nullable()->comment('目标阶段ID')->index();
            $table->tinyInteger('action_type')->default(1)->comment('动作类型: 1-流转, 2-备注, 3-撤回, 4-淘汰, 5-录用');
            $table->unsignedBigInteger('operator_user_id')->nullable()->comment('操作人用户ID')->index();
            $table->string('note')->nullable()->comment('备注');
            $table->timestamp('happened_at')->nullable()->comment('发生时间')->index();
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'application_id']);
            $table->comment('招聘投递流转记录表');
        });

        Schema::create('rc_interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('application_id')->comment('投递ID')->index();
            $table->unsignedBigInteger('stage_id')->nullable()->comment('阶段ID')->index();
            $table->unsignedBigInteger('interviewer_user_id')->nullable()->comment('面试官用户ID')->index();
            $table->string('interviewer_name')->nullable()->comment('面试官姓名');
            $table->timestamp('interview_at')->nullable()->comment('面试时间')->index();
            $table->integer('duration_mins')->nullable()->comment('时长(分钟)');
            $table->tinyInteger('mode')->default(1)->comment('面试方式: 1-线上, 2-线下, 3-电话');
            $table->tinyInteger('status')->default(0)->comment('状态: 0-待安排, 1-已安排, 2-已完成, 3-已取消');
            $table->tinyInteger('result')->default(0)->comment('结果: 0-待评估, 1-通过, 2-不通过, 3-待定');
            $table->string('location')->nullable()->comment('面试地点');
            $table->string('meeting_url')->nullable()->comment('线上会议地址');
            $table->text('note')->nullable()->comment('面试备注');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
            $table->comment('招聘面试表');
        });

        Schema::create('rc_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('application_id')->comment('投递ID')->index();
            $table->string('offer_no', 64)->comment('Offer编号')->unique();
            $table->decimal('salary_min', 10, 2)->nullable()->comment('最低薪资');
            $table->decimal('salary_max', 10, 2)->nullable()->comment('最高薪资');
            $table->tinyInteger('salary_unit')->default(1)->comment('薪资单位: 1-月, 2-日, 3-时');
            $table->date('entry_date')->nullable()->comment('入职日期')->index();
            $table->date('expire_date')->nullable()->comment('Offer过期日期')->index();
            $table->tinyInteger('status')->default(0)->comment('状态: 0-草稿, 1-已发送, 2-已接受, 3-已拒绝, 4-已过期, 5-已撤销');
            $table->timestamp('sent_at')->nullable()->comment('发送时间')->index();
            $table->timestamp('replied_at')->nullable()->comment('回复时间');
            $table->text('note')->nullable()->comment('备注');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'application_id'], name: 'rc_offers_company_id_application_id_unique');
            $table->comment('招聘Offer表');
        });

        Schema::create('rc_talent_pools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->string('code', 64)->comment('人才库编码');
            $table->string('name')->comment('人才库名称');
            $table->text('description')->nullable()->comment('人才库描述');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code'], name: 'rc_talent_pools_company_id_code_unique');
            $table->comment('招聘人才库表');
        });

        Schema::create('rc_talent_pool_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('talent_pool_id')->comment('人才库ID')->index();
            $table->unsignedBigInteger('candidate_user_id')->comment('候选人用户ID')->index();
            $table->unsignedBigInteger('resume_id')->nullable()->comment('来源简历ID')->index();
            $table->tinyInteger('source_type')->default(1)->comment('来源类型: 1-主动加入, 2-职位沉淀, 3-导入, 4-推荐');
            $table->string('note')->nullable()->comment('备注');
            $table->timestamp('added_at')->nullable()->comment('加入时间')->index();
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'talent_pool_id', 'candidate_user_id'], name: 'rc_talent_pool_members_company_id_pool_candidate_unique');
            $table->comment('招聘人才库成员表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_talent_pool_members');
        Schema::dropIfExists('rc_talent_pools');
        Schema::dropIfExists('rc_offers');
        Schema::dropIfExists('rc_interviews');
        Schema::dropIfExists('rc_application_flows');
        Schema::dropIfExists('rc_applications');
        Schema::dropIfExists('rc_job_stages');
        Schema::dropIfExists('rc_jobs');
        Schema::dropIfExists('rc_resumes');
        Schema::dropIfExists('rc_user_identities');
    }
};
