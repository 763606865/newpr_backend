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
        Schema::create('rc_school_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('type')->default(0)->comment('活动类型：0-招聘会；1-宣讲会；2-双选会');
            $table->string('title')->comment('活动标题');
            $table->text('cover_image')->nullable()->comment('活动封面图');
            $table->text('description')->nullable()->comment('活动描述');
            $table->text('link_url')->nullable()->comment('活动外链地址');
            $table->string('province_code', 30)->nullable()->comment('省')->index();
            $table->string('city_code', 30)->nullable()->comment('市')->index();
            $table->string('district_code', 30)->nullable()->comment('区/县')->index();
            $table->string('address', 200)->nullable()->comment('地址');
            $table->timestamp('register_start_date')->nullable()->comment('报名开始日期');
            $table->timestamp('register_end_date')->nullable()->comment('报名截止日期');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->string('organizer_type', 32)->nullable()->comment('主办方多态类型：school / company / area');
            $table->unsignedBigInteger('organizer_id')->nullable()->comment('主办方ID');
            $table->string('contact_name', 50)->nullable()->comment('对接负责人');
            $table->string('contact_phone', 20)->nullable()->comment('联系电话');
            $table->tinyInteger('status')->default(0)->comment('状态：0-草稿；1-已发布；2-已结束');
            $table->boolean('is_hot')->default(false)->comment('热门活动');
            $table->integer('sort')->default(0)->comment('排序');
            $table->text('files')->nullable()->comment('相关文件');
            $table->json('extra')->nullable()->comment('扩展数据');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('校园活动表');
            $table->index(['organizer_type', 'organizer_id']);
        });
        Schema::create('rc_school_activity_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id')->comment('活动ID')->index();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('activity_booth_id')->nullable()->comment('活动展位ID rc_school_activity_booths.id')->index();
            $table->tinyInteger('join_source')->comment('报名来源：0-院校后台邀约；1-企业自主申请');
            $table->tinyInteger('apply_status')->default(0)->comment('申请状态：0-待审核；1-通过；2-驳回');
            $table->timestamp('apply_at')->comment('报名提交时间');
            $table->text('remark')->nullable()->comment('报名备注、院校审核意见');
            $table->timestamps();
            $table->unique(['activity_id', 'company_id']);
            $table->comment('活动关联企业表');
        });
        Schema::create('rc_school_activity_schools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->timestamps();
            $table->unique(['activity_id', 'school_id']);
            $table->comment('活动关联学校表');
        });
        Schema::create('rc_school_activity_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id')->comment('校园活动ID')->index();
            $table->unsignedBigInteger('company_id')->comment('参会企业ID')->index();
            $table->unsignedBigInteger('school_activity_company_id')->comment('关联企业活动报名单ID rc_school_activity_companies.id')->index();
            $table->unsignedBigInteger('job_id')->comment('企业基础职位ID')->index();
            $table->tinyInteger('audit_status')->default(0)->comment('审核状态：0-pending待审核；1-pass审核通过；2-reject驳回');
            $table->text('reject_reason')->nullable()->comment('岗位驳回原因');
            $table->timestamp('audit_at')->nullable()->comment('审核操作时间');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('活动绑定招聘岗位表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_school_activity_jobs');
        Schema::dropIfExists('rc_school_activity_schools');
        Schema::dropIfExists('rc_school_activity_companies');
        Schema::dropIfExists('rc_school_activities');
    }
};
