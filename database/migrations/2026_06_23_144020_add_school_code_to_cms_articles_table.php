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
        Schema::table('cms_articles', function (Blueprint $table) {
            $table->string('school_code', 32)->nullable()->after('city_code')->comment('院校代码(为空表示平台资讯)')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_articles', function (Blueprint $table) {
            $table->dropColumn('school_code');
        });
    }
};
