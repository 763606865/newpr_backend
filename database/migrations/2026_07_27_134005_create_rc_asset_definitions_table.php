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
        Schema::create('rc_asset_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('asset_code', 64)->unique()->comment('唯一权益编码');
            $table->string('asset_name', 100)->comment('权益名称');
            $table->tinyInteger('owner_type')->default(0)->comment('适用主体: 0-通用, 1-企业, 2-个人');
            $table->tinyInteger('asset_type')->default(1)->comment('权益类型: 1-次数, 2-时长, 3-额度, 4-会员');
            $table->string('consume_scene', 64)->nullable()->comment('消费场景编码');
            $table->string('unit', 20)->default('次')->comment('权益单位');
            $table->unsignedInteger('default_duration')->default(0)->comment('默认有效期(天)，0=永久');
            $table->text('description')->nullable()->comment('权益说明');
            $table->tinyInteger('status')->default(1)->comment('状态: 0-禁用, 1-启用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展配置');
            $table->timestamps();
            $table->index(['owner_type', 'status']);
            $table->index(['consume_scene', 'status']);
            $table->comment('RC商业化权益定义表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_asset_definitions');
    }
};
