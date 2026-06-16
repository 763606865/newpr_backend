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
        if (
            Schema::hasColumn('cms_announcements', 'city_code')
            && ! Schema::hasColumn('cms_announcements', 'province_code')
        ) {
            Schema::table('cms_announcements', function (Blueprint $table) {
                $table->dropIndex('cms_announcements_city_code_index');
                $table->dropColumn('city_code');
            });
        }

        Schema::table('cms_announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_announcements', 'organization_type')) {
                $table->nullableMorphs('organization', 'cms_announcements_organization_index');
            }

            if (! Schema::hasColumn('cms_announcements', 'publisher_name')) {
                $table->string('publisher_name')->nullable()->comment('发布人名称');
            }

            if (! Schema::hasColumn('cms_announcements', 'publisher_type')) {
                $table->unsignedTinyInteger('publisher_type')->default(0)->comment('发布人类型，见 CmsAnnouncementPublisherType');
            }

            if (! Schema::hasColumn('cms_announcements', 'province_code')) {
                $table->string('province_code', 20)->nullable()->comment('省份编码')->index();
            }

            if (! Schema::hasColumn('cms_announcements', 'city_code')) {
                $table->string('city_code', 20)->nullable()->comment('城市编码')->index();
            }

            if (! Schema::hasColumn('cms_announcements', 'district_code')) {
                $table->string('district_code', 20)->nullable()->comment('区县编码')->index();
            }

            if (! Schema::hasColumn('cms_announcements', 'area_code')) {
                $table->string('area_code', 20)->nullable()->comment('行政区划编码')->index();
            }

            if (! Schema::hasColumn('cms_announcements', 'read_count')) {
                $table->unsignedInteger('read_count')->default(0)->comment('阅读人数')->index();
            }

            if (! Schema::hasColumn('cms_announcements', 'files')) {
                $table->json('files')->nullable()->comment('附件列表');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_announcements', function (Blueprint $table) {
            if (Schema::hasColumn('cms_announcements', 'organization_type')) {
                $table->dropMorphs('organization', 'cms_announcements_organization_index');
            }

            $columnsToDrop = array_values(array_filter([
                'publisher_name',
                'publisher_type',
                'province_code',
                'district_code',
                'area_code',
                'read_count',
                'files',
            ], fn (string $column): bool => Schema::hasColumn('cms_announcements', $column)));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }

            if (
                Schema::hasColumn('cms_announcements', 'city_code')
                && Schema::hasColumn('cms_announcements', 'province_code')
            ) {
                $table->dropIndex('cms_announcements_city_code_index');
                $table->dropColumn('city_code');
            }
        });

        if (! Schema::hasColumn('cms_announcements', 'city_code')) {
            Schema::table('cms_announcements', function (Blueprint $table) {
                $table->string('city_code', 32)
                    ->nullable()
                    ->comment('城市编码(为空表示全站可用)')
                    ->index()
                    ->after('id');
            });
        }
    }
};
