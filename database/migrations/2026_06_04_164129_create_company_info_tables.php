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
        Schema::create('company_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->tinyInteger('license_type')->comment('证件类型: 1-营业执照, 2-食品经营许可证, 3-资质证书, 4-其他')->index();
            $table->string('name')->comment('证件名称');
            $table->string('license_no', 100)->nullable()->comment('证件编号');
            $table->string('issuer')->nullable()->comment('发证机关');
            $table->date('issue_date')->nullable()->comment('发证日期')->index();
            $table->date('expire_date')->nullable()->comment('有效期至（null 表示长期有效）')->index();
            $table->string('file_url')->nullable()->comment('证件文件地址');
            $table->string('file_name')->nullable()->comment('证件文件名称');
            $table->string('file_ext', 16)->nullable()->comment('文件后缀');
            $table->tinyInteger('is_primary')->default(0)->comment('是否主证件: 1-是, 0-否');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->text('remark')->nullable()->comment('备注');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'license_type']);
            $table->index(['company_id', 'status']);
            $table->comment('企业证件表');
        });

        Schema::create('company_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->tinyInteger('contact_type')->comment('联系人类型: 1-法定代表人, 2-股东, 3-联系人, 4-实际控制人, 5-其他')->index();
            $table->string('name', 50)->comment('姓名');
            $table->char('id_card', 18)->nullable()->comment('身份证号');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('position', 100)->nullable()->comment('职务/头衔');
            $table->decimal('share_ratio', 5, 2)->nullable()->comment('持股比例（%）');
            $table->string('address')->nullable()->comment('联系地址');
            $table->tinyInteger('is_primary')->default(0)->comment('是否主要联系人: 1-是, 0-否');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用');
            $table->text('remark')->nullable()->comment('备注');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'contact_type']);
            $table->index(['company_id', 'status']);
            $table->index('id_card');
            $table->comment('企业联系人/股东表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_contacts');
        Schema::dropIfExists('company_licenses');
    }
};
