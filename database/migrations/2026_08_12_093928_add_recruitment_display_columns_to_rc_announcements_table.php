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
        Schema::table('rc_announcements', function (Blueprint $table) {
            $table->unsignedTinyInteger('announcement_type')->nullable()->after('publisher_type')->comment('公告业务类型，见 RcAnnouncementType');
            $table->unsignedInteger('recruitment_count')->nullable()->after('major_requirement')->comment('招录或招聘人数');
            $table->string('registration_url')->nullable()->after('link_url')->comment('报名入口地址');
            $table->json('attachments')->nullable()->after('registration_url')->comment('公告附件列表');

            $table->index('announcement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_announcements', function (Blueprint $table) {
            $table->dropIndex(['announcement_type']);
            $table->dropColumn([
                'announcement_type',
                'recruitment_count',
                'registration_url',
                'attachments',
            ]);
        });
    }
};
