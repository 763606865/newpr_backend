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
        Schema::create('cms_home_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('module_type')->comment('模块类型：1-紧急招聘；2-热招职位；3-名企招聘');
            $table->string('recommendable_type', 32)->comment('推荐对象类型：job-职位；company-企业');
            $table->unsignedBigInteger('recommendable_id')->comment('推荐对象ID');
            $table->string('city_code', 32)->nullable()->comment('城市编码(为空表示全站可用)')->index();
            $table->string('title')->nullable()->comment('推荐标题');
            $table->string('cover_image')->nullable()->comment('推荐展示图');
            $table->string('link_url')->nullable()->comment('跳转链接');
            $table->timestamp('start_at')->nullable()->comment('推荐开始时间')->index();
            $table->timestamp('end_at')->nullable()->comment('推荐结束时间')->index();
            $table->tinyInteger('status')->default(1)->comment('状态：0-禁用；1-启用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->unsignedBigInteger('order_id')->nullable()->comment('关联订单ID(预留)')->index();
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module_type', 'status', 'sort']);
            $table->index(['recommendable_type', 'recommendable_id'], 'cms_home_recommendations_recommendable_index');
            $table->comment('CMS首页推荐位表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_home_recommendations');
    }
};
