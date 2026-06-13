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
        Schema::table('rc_offers', function (Blueprint $table): void {
            $table->unsignedBigInteger('receive_user_id')->comment('接收用户ID')->index()->after('id');
            $table->unsignedBigInteger('receive_user_identity_id')->nullable()->comment('接收身份ID')->index()->after('receive_user_id');
            $table->decimal('salary', 10, 2)->nullable()->after('offer_no')->comment('确认薪资');
            $table->unsignedTinyInteger('has_probation')->default(0)->after('salary_unit')->comment('是否有试用期: 0-否, 1-是');
            $table->text('remuneration_note')->nullable()->after('has_probation')->comment('薪酬说明');
            $table->text('attendance_note')->nullable()->after('remuneration_note')->comment('考勤说明');
            $table->timestamp('email_sent_at')->nullable()->after('extra')->comment('邮件发送时间');
            $table->timestamp('sms_sent_at')->nullable()->after('email_sent_at')->comment('短信发送时间');
        });

        Schema::table('rc_offers', function (Blueprint $table): void {
            $table->dropColumn(['salary_min', 'salary_max']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_offers', function (Blueprint $table): void {
            $table->decimal('salary_min', 10, 2)->nullable()->after('offer_no')->comment('最低薪资');
            $table->decimal('salary_max', 10, 2)->nullable()->after('salary_min')->comment('最高薪资');
        });

        Schema::table('rc_offers', function (Blueprint $table): void {
            $table->dropColumn([
                'receive_user_id',
                'receive_user_identity_id',
                'salary',
                'has_probation',
                'remuneration_note',
                'attendance_note',
                'email_sent_at',
                'sms_sent_at',
            ]);
        });
    }
};
