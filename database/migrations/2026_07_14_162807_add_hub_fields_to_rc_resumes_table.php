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
        Schema::table('rc_resumes', function (Blueprint $table) {
            $table->string('ext_source')->nullable()->comment('外部来源')->after('status');
            $table->string('ext_id')->nullable()->comment('外部id')->after('ext_source');
            $table->index(['ext_source', 'ext_id'], 'rc_resumes_ext_source_ext_id_index');
        });

        // 为职位表添加相同字段，以便存储外部职位来源标识
        Schema::table('rc_jobs', function (Blueprint $table) {
            $table->string('ext_source')->nullable()->comment('外部来源')->after('status');
            $table->string('ext_id')->nullable()->comment('外部id')->after('ext_source');
            $table->index(['ext_source', 'ext_id'], 'rc_jobs_ext_source_ext_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_resumes', function (Blueprint $table) {
            $table->dropIndex('rc_resumes_ext_source_ext_id_index');
            $table->dropColumn(['ext_source', 'ext_id']);
        });

        Schema::table('rc_jobs', function (Blueprint $table) {
            $table->dropIndex('rc_jobs_ext_source_ext_id_index');
            $table->dropColumn(['ext_source', 'ext_id']);
        });
    }
};
