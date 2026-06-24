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
        Schema::create('rc_company_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique()->comment('企业ID');
            $table->string('short_name', 100)->nullable()->comment('企业简称/品牌名');
            $table->string('logo')->nullable()->comment('Logo OSS 路径');
            $table->string('city_code', 32)->nullable()->comment('主办公城市编码')->index();
            $table->tinyInteger('scale_type')->nullable()->comment('公司规模')->index();
            $table->tinyInteger('nature_type')->nullable()->comment('公司性质')->index();
            $table->json('industry_codes')->nullable()->comment('所属行业 codes');
            $table->date('founded_at')->nullable()->comment('成立日期');
            $table->string('website')->nullable()->comment('官网');
            $table->text('introduction')->nullable()->comment('企业简介');
            $table->json('benefit_tags')->nullable()->comment('福利标签 codes');
            $table->tinyInteger('funding_stage')->nullable()->comment('融资阶段');
            $table->tinyInteger('profile_status')->default(0)->comment('资料状态: 0-草稿, 1-已完善, 2-审核中')->index();
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();

            $table->comment('企业招聘展示资料表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_company_profiles');
    }
};
