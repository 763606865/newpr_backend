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
        Schema::table('rc_jobs', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_urgent')->default(0)->comment('是否紧急招聘：1-是；0-否')->after('status');
            $table->timestamp('urgent_until')->nullable()->comment('紧急招聘截止时间')->after('is_urgent');
            $table->index(['is_urgent', 'urgent_until']);
        });

        Schema::table('rc_company_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_brand')->default(0)->comment('是否名企：1-是；0-否')->after('profile_status');
            $table->integer('brand_sort')->default(0)->comment('名企排序')->after('is_brand');
            $table->index(['is_brand', 'brand_sort']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_jobs', function (Blueprint $table) {
            $table->dropIndex(['is_urgent', 'urgent_until']);
            $table->dropColumn(['is_urgent', 'urgent_until']);
        });

        Schema::table('rc_company_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_brand', 'brand_sort']);
            $table->dropColumn(['is_brand', 'brand_sort']);
        });
    }
};
