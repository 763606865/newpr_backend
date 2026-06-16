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
        Schema::create('school_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('school_code', 30)->nullable()->comment('学校代码')->index();
            $table->string('short_name', 100)->nullable()->comment('学校简称');
            $table->string('province_code', 30)->nullable()->comment('省')->index();
            $table->string('city_code', 30)->nullable()->comment('市')->index();
            $table->string('district_code', 30)->nullable()->comment('区/县')->index();
            $table->string('address', 200)->nullable()->comment('地址');
            $table->string('contact_name', 50)->nullable()->comment('校方对接总负责人');
            $table->string('contact_phone', 20)->nullable()->comment('联系电话');
            $table->string('contact_email', 100)->nullable()->comment('就业办邮箱');
            $table->text('qualification_file')->nullable()->comment('资质证明');
            $table->string('competent_dept', 50)->nullable()->comment('主管部门');
            $table->json('education_levels')->nullable()->comment('办学层次');
            $table->tinyInteger('main_education_level')->nullable()->comment('主办学层次');
            $table->string('logo', 255)->nullable()->comment('校徽logo');
            $table->string('banner', 255)->nullable()->comment('首页横幅图');
            $table->boolean('allow_company_apply_activity')->default(true)->comment('是否允许企业自主发起进校宣讲申请');
            $table->boolean('allow_company_cooperate_apply')->default(true)->comment('是否开放校企对接申请入口');
            $table->unsignedInteger('campus_count')->default(0)->comment('校区数量');
            $table->unsignedInteger('department_count')->default(0)->comment('学院数量');
            $table->unsignedInteger('cooperate_company_count')->default(0)->comment('合作企业总数');
            $table->unsignedInteger('activity_total')->default(0)->comment('累计举办宣讲/双选会场次');
            $table->longText('intro')->nullable()->comment('院校简介，用于校招页面展示');
            $table->tinyInteger('status')->default(1)->comment('院校状态：0-禁用；1-正常；2-审核中');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->comment('学校资料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_profiles');
    }
};
