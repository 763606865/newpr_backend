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
        Schema::create('c_menus', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父菜单ID 0=顶级');
            $table->string('menu_name', 50)->comment('菜单名称');
            $table->string('menu_code', 100)->nullable()->comment('菜单唯一标识')->unique();
            $table->tinyInteger('menu_type')->default(1)->comment('1=菜单 2=按钮/权限点');
            $table->string('path', 255)->nullable()->comment('路由路径');
            $table->string('component', 255)->nullable()->comment('前端组件路径');
            $table->string('icon', 100)->nullable()->comment('菜单图标');
            $table->integer('sort')->default(0)->comment('显示排序');
            $table->tinyInteger('visible')->default(1)->comment('0=隐藏 1=显示');
            $table->mediumText('style')->nullable()->comment('样式扩展属性');
            $table->mediumText('extra')->nullable()->comment('其他扩展属性');
            $table->timestamps();
            $table->comment('功能菜单表');
        });
        Schema::create('c_features', static function (Blueprint $table) {
            $table->id();
            $table->string('feature_name', 50)->comment('功能名称');
            $table->string('feature_code', 100)->comment('功能唯一编码')->unique();
            $table->unsignedBigInteger('menu_id')->comment('所属菜单ID');
            $table->string('description', 255)->nullable()->comment('功能描述');
            $table->tinyInteger('status')->default(1)->comment('0=禁用 1=启用');
            $table->timestamps();
            $table->comment('系统功能点表');
        });
        Schema::create('biz_plans', static function (Blueprint $table) {
            $table->id();
            $table->string('plan_name', 50)->comment('方案名称');
            $table->string('plan_code', 50)->comment('唯一标识')->unique();
            $table->decimal('price')->default(0.00)->comment('方案价格');
            $table->integer('duration')->default(0)->comment('方案时长(天) 0=永久');
            $table->integer('sort')->default(0)->comment('排序');
            $table->text('remark')->nullable()->comment('方案描述');
            $table->tinyInteger('status')->default(1)->comment('0=禁用 1=启用');
            $table->mediumText('extra')->nullable()->comment('其他扩展属性');
            $table->timestamps();
            $table->comment('企业方案表');
        });
        Schema::create('biz_plan_c_features', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id')->comment('方案ID');
            $table->unsignedBigInteger('feature_id')->comment('功能ID');
            $table->timestamps();
            $table->comment('方案功能关联表');
        });
        Schema::create('company_biz_plans', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('ship_id')->comment('企业历史方案关联ID')->index();
            $table->unsignedBigInteger('plan_id')->comment('方案ID')->index();
            $table->dateTime('start_time')->nullable()->comment('方案开始时间');
            $table->dateTime('end_time')->nullable()->comment('方案结束时间');
            $table->tinyInteger('is_current')->default(1)->comment('是否当前有效：0-否 1-是');
            $table->tinyInteger('status')->default(1)->comment('0=失效 1=生效中 2=暂停维护');
            $table->mediumText('extra')->nullable()->comment('其他扩展属性');
            $table->timestamps();
            $table->comment('企业当前方案关联表');
        });
        Schema::create('ship_company_biz_plans', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->unsignedBigInteger('plan_id')->comment('方案ID')->index();
            $table->string('plan_name', 50)->comment('方案名称');
            $table->string('plan_code', 50)->comment('唯一标识');
            $table->decimal('original_price')->comment('原价');
            $table->decimal('pay_amount')->comment('实付金额');
            $table->mediumText('menus')->comment('菜单点');
            $table->mediumText('features')->comment('功能点');
            $table->mediumText('quota')->nullable()->comment('配额');
            $table->dateTime('start_time')->nullable()->comment('方案开始时间');
            $table->dateTime('end_time')->nullable()->comment('方案结束时间');
            $table->mediumText('remark')->nullable()->comment('备注');
            $table->mediumText('extra')->nullable()->comment('其他扩展属性');
            $table->timestamps();
            $table->comment('企业历史方案关联表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_company_biz_plans');
        Schema::dropIfExists('company_biz_plans');
        Schema::dropIfExists('biz_plan_c_features');
        Schema::dropIfExists('biz_plans');
        Schema::dropIfExists('c_features');
        Schema::dropIfExists('c_menus');
    }
};
