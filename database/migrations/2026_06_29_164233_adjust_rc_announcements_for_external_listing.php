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
            if (Schema::hasColumn('rc_announcements', 'employment_type')) {
                $table->dropIndex(['employment_type']);
                $table->dropColumn('employment_type');
            }

            if (! Schema::hasColumn('rc_announcements', 'employment_types')) {
                $table->json('employment_types')
                    ->nullable()
                    ->after('link_url')
                    ->comment('工作类型列表，见 RcJobEmploymentType，如 [3,4] 表示实习+校招');
            }

            if (! Schema::hasColumn('rc_announcements', 'apply_deadline_type')) {
                $table->unsignedTinyInteger('apply_deadline_type')
                    ->default(1)
                    ->after('apply_end_at')
                    ->comment('截止类型: 1-指定日期, 2-招满即止');
            }

            if (! Schema::hasColumn('rc_announcements', 'is_nationwide')) {
                $table->unsignedTinyInteger('is_nationwide')
                    ->default(0)
                    ->after('major_requirement')
                    ->comment('是否全国招聘: 1-是, 0-否');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_announcements', function (Blueprint $table) {
            if (Schema::hasColumn('rc_announcements', 'is_nationwide')) {
                $table->dropColumn('is_nationwide');
            }

            if (Schema::hasColumn('rc_announcements', 'apply_deadline_type')) {
                $table->dropColumn('apply_deadline_type');
            }

            if (Schema::hasColumn('rc_announcements', 'employment_types')) {
                $table->dropColumn('employment_types');
            }

            if (! Schema::hasColumn('rc_announcements', 'employment_type')) {
                $table->unsignedTinyInteger('employment_type')
                    ->default(1)
                    ->after('link_url')
                    ->comment('工作类型，见 RcJobEmploymentType');

                $table->index('employment_type');
            }
        });
    }
};
