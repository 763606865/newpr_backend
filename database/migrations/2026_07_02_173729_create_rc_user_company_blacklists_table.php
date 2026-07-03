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
        Schema::create('rc_user_company_blacklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('求职者用户ID')->index();
            $table->unsignedBigInteger('company_id')->comment('被屏蔽企业ID')->index();
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();

            $table->unique(['user_id', 'company_id'], 'rc_user_company_blacklists_user_company_unique');
            $table->index(['company_id', 'user_id'], 'rc_user_company_blacklists_company_user_index');
            $table->comment('求职者企业黑名单表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_user_company_blacklists');
    }
};
