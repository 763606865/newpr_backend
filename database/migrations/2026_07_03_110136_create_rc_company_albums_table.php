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
        Schema::create('rc_company_albums', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->string('title', 100)->nullable()->comment('图片标题');
            $table->string('image')->comment('图片 OSS 路径');
            $table->string('description', 500)->nullable()->comment('图片描述');
            $table->tinyInteger('type')->default(1)->comment('图片类型: 1-办公环境, 2-企业文化相册，3-企业荣誉相册, 4-其他');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'sort'], 'rc_company_albums_company_status_sort_index');
            $table->comment('企业相册表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_company_albums');
    }
};
