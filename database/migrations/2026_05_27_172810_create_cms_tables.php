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
        Schema::create('cms_banner_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('版位名称');
            $table->string('code', 64)->unique()->comment('版位编码');
            $table->integer('width')->nullable()->comment('建议宽度');
            $table->integer('height')->nullable()->comment('建议高度');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户Banner版位表');
        });

        Schema::create('cms_banners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_id')->comment('版位ID')->index();
            $table->string('city_code', 32)->nullable()->comment('城市编码(为空表示全站可用)')->index();
            $table->string('title')->comment('标题');
            $table->string('image')->comment('图片地址');
            $table->tinyInteger('link_type')->default(1)->comment('链接类型: 1-站内, 2-站外, 3-无跳转');
            $table->string('link_url')->nullable()->comment('跳转地址');
            $table->tinyInteger('target')->default(1)->comment('打开方式: 1-当前页, 2-新窗口');
            $table->timestamp('start_at')->nullable()->comment('生效开始时间');
            $table->timestamp('end_at')->nullable()->comment('生效结束时间');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户Banner表');
        });

        Schema::create('cms_ad_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('广告位名称');
            $table->string('code', 64)->unique()->comment('广告位编码');
            $table->tinyInteger('type')->default(1)->comment('广告位类型: 1-图片, 2-文本, 3-代码');
            $table->integer('width')->nullable()->comment('建议宽度');
            $table->integer('height')->nullable()->comment('建议高度');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户广告位表');
        });

        Schema::create('cms_ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('slot_id')->comment('广告位ID')->index();
            $table->string('city_code', 32)->nullable()->comment('城市编码(为空表示全站可用)')->index();
            $table->string('title')->comment('广告标题');
            $table->tinyInteger('type')->default(1)->comment('广告类型: 1-图片, 2-文本, 3-代码');
            $table->string('image')->nullable()->comment('图片地址');
            $table->text('text_content')->nullable()->comment('文本内容');
            $table->longText('code_content')->nullable()->comment('代码内容');
            $table->string('link_url')->nullable()->comment('跳转地址');
            $table->timestamp('start_at')->nullable()->comment('生效开始时间');
            $table->timestamp('end_at')->nullable()->comment('生效结束时间');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户广告表');
        });

        Schema::create('cms_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级菜单ID')->index();
            $table->string('name')->comment('菜单名称');
            $table->string('code', 64)->nullable()->unique()->comment('菜单编码');
            $table->tinyInteger('link_type')->default(1)->comment('链接类型: 1-站内, 2-站外, 3-无跳转');
            $table->string('link_url')->nullable()->comment('跳转地址');
            $table->string('icon')->nullable()->comment('菜单图标');
            $table->string('image')->nullable()->comment('菜单图片');
            $table->tinyInteger('target')->default(1)->comment('打开方式: 1-当前页, 2-新窗口');
            $table->tinyInteger('is_show')->default(1)->comment('是否展示: 1-是, 0-否');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamp('start_at')->nullable()->comment('生效开始时间');
            $table->timestamp('end_at')->nullable()->comment('生效结束时间');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户首页菜单表');
        });

        Schema::create('cms_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('city_code', 32)->nullable()->comment('城市编码(为空表示全站可用)')->index();
            $table->string('title')->comment('公告标题');
            $table->string('sub_title')->nullable()->comment('公告副标题');
            $table->string('link_url')->nullable()->comment('公告链接');
            $table->tinyInteger('type')->default(1)->comment('公告类型: 1-自发公告, 2-站点采集, 3-授权公告');
            $table->string('source_name')->nullable()->comment('来源站点/机构名称');
            $table->string('source_url')->nullable()->comment('来源地址');
            $table->string('summary', 1000)->nullable()->comment('公告摘要');
            $table->longText('content')->nullable()->comment('公告正文');
            $table->timestamp('published_at')->nullable()->comment('发布时间')->index();
            $table->timestamp('start_at')->nullable()->comment('生效开始时间');
            $table->timestamp('end_at')->nullable()->comment('生效结束时间');
            $table->tinyInteger('is_top')->default(0)->comment('是否置顶: 1-是, 0-否');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-草稿, 2-已发布, 3-下线');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户公告表');
        });

        Schema::create('cms_site_configs', function (Blueprint $table) {
            $table->id();
            $table->string('site_code', 64)->unique()->comment('站点编码');
            $table->string('city_code', 32)->nullable()->comment('城市编码(为空表示全站默认站点)')->index();
            $table->string('name')->comment('站点名称');
            $table->string('short_name')->nullable()->comment('站点简称');
            $table->string('domain')->nullable()->comment('站点域名');
            $table->string('logo')->nullable()->comment('站点Logo');
            $table->string('favicon')->nullable()->comment('站点图标');
            $table->string('slogan')->nullable()->comment('站点Slogan');
            $table->string('icp_no')->nullable()->comment('ICP备案号');
            $table->string('public_security_no')->nullable()->comment('公安备案号');
            $table->string('service_phone')->nullable()->comment('客服电话');
            $table->string('service_email')->nullable()->comment('客服邮箱');
            $table->string('seo_title')->nullable()->comment('SEO标题');
            $table->string('seo_keywords')->nullable()->comment('SEO关键词');
            $table->string('seo_description')->nullable()->comment('SEO描述');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->json('theme_config')->nullable()->comment('主题配置');
            $table->json('extra')->nullable()->comment('扩展配置');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户站点配置表');
        });

        Schema::create('cms_friend_links', function (Blueprint $table) {
            $table->id();
            $table->string('city_code', 32)->nullable()->comment('城市编码(为空表示全站可用)')->index();
            $table->string('name')->comment('友链名称');
            $table->string('url')->comment('友链地址');
            $table->string('logo')->nullable()->comment('友链Logo');
            $table->tinyInteger('target')->default(2)->comment('打开方式: 1-当前页, 2-新窗口');
            $table->string('rel')->nullable()->comment('链接关系属性(如nofollow)');
            $table->string('description')->nullable()->comment('描述');
            $table->timestamp('start_at')->nullable()->comment('生效开始时间');
            $table->timestamp('end_at')->nullable()->comment('生效结束时间');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户友情链接表');
        });

        Schema::create('cms_article_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级分类ID')->index();
            $table->string('name')->comment('分类名称');
            $table->string('slug', 128)->nullable()->comment('分类别名')->index();
            $table->string('cover')->nullable()->comment('封面图');
            $table->string('description')->nullable()->comment('分类描述');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户文章分类表');
        });

        Schema::create('cms_article_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('标签名称');
            $table->string('slug', 128)->nullable()->unique()->comment('标签别名');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户文章标签表');
        });

        Schema::create('cms_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->default(0)->comment('分类ID')->index();
            $table->string('city_code', 32)->nullable()->comment('城市编码(为空表示全站可用)')->index();
            $table->string('title')->comment('标题');
            $table->string('sub_title')->nullable()->comment('副标题');
            $table->string('slug', 191)->nullable()->unique()->comment('文章别名');
            $table->string('cover')->nullable()->comment('封面图');
            $table->string('summary', 1000)->nullable()->comment('摘要');
            $table->string('author')->nullable()->comment('作者');
            $table->string('source_name')->nullable()->comment('来源名称');
            $table->string('source_url')->nullable()->comment('来源链接');
            $table->tinyInteger('is_top')->default(0)->comment('是否置顶: 1-是, 0-否');
            $table->tinyInteger('is_recommend')->default(0)->comment('是否推荐: 1-是, 0-否');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-草稿, 2-已发布, 3-下线');
            $table->timestamp('published_at')->nullable()->comment('发布时间')->index();
            $table->unsignedBigInteger('view_count')->default(0)->comment('浏览量');
            $table->string('seo_keywords')->nullable()->comment('SEO关键词');
            $table->string('seo_description')->nullable()->comment('SEO描述');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('门户文章表');
        });

        Schema::create('cms_article_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id')->unique()->comment('文章ID');
            $table->longText('content')->nullable()->comment('正文内容');
            $table->tinyInteger('content_type')->default(1)->comment('内容类型: 1-HTML, 2-Markdown');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->comment('门户文章内容表');
        });

        Schema::create('cms_article_tag_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id')->comment('文章ID')->index();
            $table->unsignedBigInteger('tag_id')->comment('标签ID')->index();
            $table->timestamps();
            $table->unique(['article_id', 'tag_id']);
            $table->comment('门户文章标签关联表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_article_tag_relations');
        Schema::dropIfExists('cms_article_contents');
        Schema::dropIfExists('cms_articles');
        Schema::dropIfExists('cms_article_tags');
        Schema::dropIfExists('cms_article_categories');
        Schema::dropIfExists('cms_friend_links');
        Schema::dropIfExists('cms_site_configs');
        Schema::dropIfExists('cms_announcements');
        Schema::dropIfExists('cms_menus');
        Schema::dropIfExists('cms_ads');
        Schema::dropIfExists('cms_ad_slots');
        Schema::dropIfExists('cms_banners');
        Schema::dropIfExists('cms_banner_positions');
    }
};
