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
        Schema::create('rc_school_booths', function (Blueprint $table) {
            $table->id();
            $table->string('school_code', 30)->nullable()->comment('学校代码')->index();
            $table->string('province_code', 30)->nullable()->comment('省')->index();
            $table->string('city_code', 30)->nullable()->comment('市')->index();
            $table->string('district_code', 30)->nullable()->comment('区/县')->index();
            $table->string('address', 200)->nullable()->comment('地址');
            $table->string('name', 100)->comment('展位名称');
            $table->text('image')->nullable()->comment('展位平面图');
            $table->unsignedInteger('area_size')->nullable()->comment('场地总占地面积㎡');
            $table->unsignedInteger('max_people')->nullable()->comment('场地最大容纳人数');
            $table->unsignedInteger('total_booth_count')->default(0)->comment('该模板下总展位数量，自动计算回填');
            $table->text('description')->nullable()->comment('场地说明');
            $table->json('rule')->nullable()->comment('分区规则');
            $table->tinyInteger('status')->default(1)->comment('状态：0-禁用；1-启用');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('展位表');
        });
        Schema::create('rc_school_booth_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booth_id')->comment('展位ID')->index();
            $table->string('code', 20)->nullable()->comment('展区编码');
            $table->string('name', 100)->comment('展区名称');
            $table->unsignedInteger('area_size')->nullable()->comment('分区占地面积㎡');
            $table->unsignedInteger('max_people')->nullable()->comment('分区最大容纳人数');
            $table->text('map_image')->nullable()->comment('分区独立平面图');
            $table->unsignedInteger('start_no')->comment('展位起始号');
            $table->unsignedInteger('end_no')->comment('展位结束号');
            $table->unsignedInteger('total_booth_count')->default(0)->comment('分区展位总数');
            $table->unsignedInteger('max_company_count')->nullable()->comment('单个展位最多企业人数');
            $table->text('extra')->nullable()->comment('扩展数据');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->comment('展区表');
        });
        Schema::create('rc_school_activity_booths', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id')->comment('活动ID')->index();
            $table->unsignedBigInteger('booth_id')->comment('展位ID')->index();
            $table->unsignedBigInteger('school_id')->nullable()->comment('学校ID')->index();
            $table->unsignedBigInteger('company_id')->nullable()->comment('企业ID')->index();
            $table->unsignedBigInteger('booth_area_id')->nullable()->comment('展区ID')->index();
            $table->string('booth_area_code', 20)->comment('展区Code快照');
            $table->string('booth_area_name', 100)->comment('展区名称快照');
            $table->string('booth_no', 50)->comment('展位编号');
            $table->decimal('price', 10, 2)->nullable()->comment('价格');
            $table->timestamp('start_at')->nullable()->comment('生效开始时间');
            $table->timestamp('end_at')->nullable()->comment('生效结束时间');
            $table->tinyInteger('status')->default(1)->comment('状态：0-禁用；1-启用');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['activity_id', 'booth_no']);
            $table->comment('活动展位表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_school_activity_booths');
        Schema::dropIfExists('rc_school_booth_areas');
        Schema::dropIfExists('rc_school_booths');
    }
};
