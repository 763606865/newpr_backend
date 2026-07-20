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
        Schema::table('rc_company_profiles', function (Blueprint $table): void {
            $table->string('work_time', 100)->nullable()->after('introduction')->comment('工作作息时间（如早9晚6、09:00-18:00）');
            $table->unsignedTinyInteger('rest_type')->nullable()->after('work_time')->index()->comment('休息制度: 1-双休, 2-单休, 3-大小周, 4-排班, 5-其他');
            $table->unsignedTinyInteger('salary_pay_day')->nullable()->after('rest_type')->comment('每月发薪日（1-31）');
            $table->boolean('has_overtime_subsidy')->default(false)->after('salary_pay_day')->comment('是否有加班补助');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_company_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'work_time',
                'rest_type',
                'salary_pay_day',
                'has_overtime_subsidy',
            ]);
        });
    }
};
