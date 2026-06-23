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
        Schema::table('rc_school_activity_schools', function (Blueprint $table) {
            $table->string('contact_name', 50)->nullable()->after('school_id')->comment('院校对接联系人');
            $table->string('contact_phone', 20)->nullable()->after('contact_name')->comment('联系电话');
            $table->string('contact_email', 100)->nullable()->after('contact_phone')->comment('联系邮箱');
            $table->tinyInteger('apply_status')->nullable()->after('contact_email')->comment('进校申请状态：0-待审核；1-通过；2-驳回');
            $table->timestamp('apply_at')->nullable()->after('apply_status')->comment('申请提交时间');
            $table->text('remark')->nullable()->after('apply_at')->comment('申请备注或审核意见');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_school_activity_schools', function (Blueprint $table) {
            $table->dropColumn([
                'contact_name',
                'contact_phone',
                'contact_email',
                'apply_status',
                'apply_at',
                'remark',
            ]);
        });
    }
};
