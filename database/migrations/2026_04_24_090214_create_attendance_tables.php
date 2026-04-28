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
        Schema::create('oa_attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('公司ID')->index();
            $table->string('name')->comment('考勤规则名称');
            $table->string('code')->unique()->comment('考勤规则编码');
            $table->tinyInteger('work_type')->default(1)->comment('工作类型');
            $table->time('start_time')->nullable()->comment('上班时间');
            $table->time('end_time')->nullable()->comment('下班时间');
            $table->json('time_segments')->nullable()->comment('时间段配置');
            $table->time('core_start_time')->nullable()->comment('核心工作开始时间');
            $table->time('core_end_time')->nullable()->comment('核心工作结束时间');
            $table->decimal('required_work_hours', 4)->nullable()->comment('要求工作时长');
            $table->tinyInteger('is_overnight')->default(0)->comment('是否跨天: 1-是, 0-否');
            $table->integer('rest_duration_mins')->default(0)->comment('扣除的休息时间(分钟)，如午休');
            $table->integer('late_grace_mins')->default(0)->comment('迟到容忍分钟数');
            $table->integer('early_leave_grace_mins')->default(0)->comment('早退容忍分钟数');
            $table->integer('clock_in_window_mins')->default(30)->comment('允许上班打卡的时间窗口(分钟)');
            $table->integer('clock_out_window_mins')->default(30)->comment('允许下班打卡的时间窗口(分钟)');
            $table->json('applicable_scope')->nullable()->comment('适用范围配置，如部门ID列表、岗位列表等');
            $table->tinyInteger('status')->default(1)->comment('状态：1-启用，0-停用');
            $table->json('extra')->nullable()->comment('扩展字段，存储其他配置信息');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('考勤规则表');
        });
        Schema::create('oa_attendance_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('公司ID')->index();
            $table->unsignedBigInteger('department_id')->comment('部门ID')->index();
            $table->unsignedBigInteger('employee_id')->comment('员工ID')->index();
            $table->unsignedBigInteger('attendance_rule_id')->comment('考勤规则ID')->index();
            $table->date('effective_start_date')->comment('生效开始日期');
            $table->date('effective_end_date')->nullable()->comment('生效结束日期(为空表示长期有效)');
            $table->tinyInteger('cycle_type')->default(1)->comment('周期类型: 1-无周期(固定), 2-大小周, 3-做X休Y');
            $table->integer('work_days')->default(7)->comment('工作天数(配合周期使用，如做2休1则为2)');
            $table->integer('rest_days')->default(0)->comment('休息天数');
            $table->date('start_anchor_date')->nullable()->comment('周期锚点日期(用于计算轮班起始日)');
            $table->integer('priority')->default(0)->comment('优先级(数值越大优先级越高，用于覆盖默认设置)');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用');
            $table->json('extra')->nullable()->comment('扩展字段，存储其他配置信息');
            $table->timestamps();
            $table->comment('用户考勤表');
        });
        Schema::create('oa_attendance_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('公司ID')->index();
            $table->unsignedBigInteger('department_id')->comment('部门ID')->index();
            $table->unsignedBigInteger('employee_id')->comment('员工ID')->index();
            $table->unsignedBigInteger('attendance_rule_id')->comment('考勤规则ID')->index();
            $table->date('date')->comment('考勤日期');
            $table->dateTime('std_start_time')->nullable()->comment('标准上班开始时间(含日期)');
            $table->dateTime('std_end_time')->nullable()->comment('标准下班结束时间(含日期)');
            $table->decimal('std_work_hours', 5)->nullable()->comment('标准应出勤工时(小时)');
            $table->tinyInteger('is_rest_day')->default(0)->comment('是否休息日: 1-是, 0-否');
            $table->tinyInteger('is_overnight')->default(0)->comment('是否跨天班次');
            $table->tinyInteger('work_type')->default(1)->comment('班次模型快照: 1-固定, 2-分段, 3-弹性');
            $table->dateTime('actual_start_time')->nullable()->comment('实际最早打卡时间');
            $table->dateTime('actual_end_time')->nullable()->comment('实际最晚打卡时间');
            $table->decimal('actual_work_hours', 5)->default(0)->comment('实际出勤工时');
            $table->tinyInteger('status')->default(0)->comment('考勤状态: 0-待计算, 1-正常, 2-迟到, 3-早退, 4-缺卡, 5-旷工');
            $table->integer('late_mins')->default(0)->comment('迟到分钟数');
            $table->integer('early_leave_mins')->default(0)->comment('早退分钟数');
            $table->integer('absence_mins')->default(0)->comment('缺勤/旷工分钟数');
            $table->json('extra')->nullable()->comment('扩展字段，存储其他信息，如打卡地点、设备等');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('考勤记录表');
        });
        Schema::create('oa_leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->string('name', 50)->comment('假期名称(如：年假、病假、事假、调休假)');
            $table->string('code', 32)->comment('假期编码(如：ANNUAL, SICK)');
            $table->tinyInteger('deduction_type')->default(1)->comment('扣薪类型: 1-带薪(全薪), 2-半薪, 3-无薪');
            $table->tinyInteger('unit_type')->default(1)->comment('请假单位: 1-按天, 2-按小时');
            $table->decimal('min_duration', 4)->default(0.5)->comment('最小请假时长(如0.5天或1小时)');
            $table->tinyInteger('need_attachment')->default(0)->comment('是否必须附件(如病假条)');
            $table->tinyInteger('allow_negative')->default(0)->comment('是否允许余额为负(透支)');
            $table->integer('max_continuous_days')->nullable()->comment('最大连续请假天数(如年假不能超过15天)');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('假期类型定义表');
        });

        Schema::create('oa_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('user_id')->comment('员工ID')->index();
            $table->unsignedBigInteger('leave_type_id')->comment('假期类型ID')->index();
            $table->integer('year')->comment('归属年份(如2023)');
            $table->date('valid_start_date')->comment('有效期开始')->index();
            $table->date('valid_end_date')->comment('有效期结束')->index();
            $table->decimal('total_days')->comment('总授予额度');
            $table->decimal('used_days')->default(0)->comment('已使用额度');
            $table->decimal('balance_days')->comment('剩余额度');
            $table->unsignedBigInteger('overtime_source_id')->nullable()->comment('来源加班单ID(如果是调休)');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('假期额度表');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oa_leave_balances');
        Schema::dropIfExists('oa_leave_types');
        Schema::dropIfExists('oa_attendance_schedules');
        Schema::dropIfExists('oa_attendance_assignments');
        Schema::dropIfExists('oa_attendance_rules');
    }
};
