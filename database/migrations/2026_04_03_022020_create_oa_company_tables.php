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
        Schema::create('oa_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('企业名称');
            $table->string('credit_code')->nullable()->comment('统一社会信用代码');
            $table->string('legal_person')->nullable()->comment('法人姓名');
            $table->string('contact_phone')->nullable()->comment('联系电话');
            $table->text('address')->nullable()->comment('企业地址');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['credit_code']);
            $table->comment('企业主体表');
        });
        Schema::create('oa_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('上级部门ID');
            $table->tinyInteger('depth')->nullable()->default(1)->comment('层级');
            $table->string('name')->comment('部门名称');
            $table->tinyInteger('type')->default(1)->comment('类型');
            $table->integer('sort')->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'parent_id']);
            $table->comment('企业部门架构表');
        });
        Schema::create('oa_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name')->comment('名称');
            $table->string('code')->comment('编码');
            $table->integer('sort')->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->comment('企业职级表');
            $table->index(['company_id']);
        });
        Schema::create('oa_department_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('position_id');
            $table->comment('部门职级表');
            $table->index(['company_id']);
            $table->index(['department_id']);
            $table->unique(['company_id', 'department_id', 'position_id'], name: 'oa_department_positions_c_id_d_id_p_id_unique');
        });
        Schema::create('oa_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('关联用户ID');
            $table->unsignedBigInteger('company_id')->nullable()->comment('企业ID');
            $table->unsignedBigInteger('department_id')->nullable()->comment('部门ID');
            $table->unsignedBigInteger('position_id')->nullable()->comment('职位ID');
            $table->string('employee_no')->nullable()->comment('员工工号');
            $table->string('real_name')->comment('姓名');
            $table->string('avatar')->comment('头像');
            $table->string('email')->nullable()->comment('邮箱');
            $table->string('mobile')->nullable()->comment('手机号');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamp('entry_time')->nullable()->comment('加入时间');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id']);
            $table->index(['department_id']);
            $table->index(['position_id']);
            $table->index(['user_id']);
            $table->index(['entry_time']);
            $table->comment('员工表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oa_employees');
        Schema::dropIfExists('oa_department_positions');
        Schema::dropIfExists('oa_positions');
        Schema::dropIfExists('oa_departments');
        Schema::dropIfExists('oa_companies');
    }
};
