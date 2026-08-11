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
            $table->string('ext_source')->nullable()->after('status')->comment('外部来源');
            $table->string('ext_id')->nullable()->after('ext_source')->comment('外部公告ID');
            $table->unique(['ext_source', 'ext_id'], 'rc_announcements_ext_source_ext_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_announcements', function (Blueprint $table) {
            $table->dropUnique('rc_announcements_ext_source_ext_id_unique');
            $table->dropColumn(['ext_source', 'ext_id']);
        });
    }
};
