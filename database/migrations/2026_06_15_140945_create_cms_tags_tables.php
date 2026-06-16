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
        Schema::create('cms_tags', function (Blueprint $table) {
            $table->id();
            $table->string('category', 64)->comment('标签分类，如 rc、exam、announcement')->index();
            $table->string('name')->comment('标签名称');
            $table->string('slug', 128)->nullable()->comment('标签别名');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['category', 'name'], 'cms_tags_category_name_unique');
            $table->comment('门户通用标签表');
        });

        Schema::create('cms_tag_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id')->comment('标签ID')->index();
            $table->morphs('taggable', 'cms_tag_relations_taggable_index');
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'cms_tag_relations_unique');
            $table->comment('门户通用标签关联表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_tag_relations');
        Schema::dropIfExists('cms_tags');
    }
};
