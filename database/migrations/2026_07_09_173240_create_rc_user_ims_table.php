<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('rc_user_ims', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment('references users.id');
            $table->unsignedBigInteger('user_identity_id')->comment('references user_identity.id (optional)');
            $table->tinyInteger('identity_type')->comment('身份类型: 1-求职者, 2-招聘方, 3-校招负责人, 4-政府机构负责人, 5-猎头');
            $table->string('provider', 64)->comment('IM provider identifier, e.g. custom, rongcloud');
            $table->string('app_code')->nullable()->comment('provider app code or tenant');
            $table->string('external_user_id')->nullable()->comment('external_user_id provided to IM (optional)一般为user_identity.id加密后的uuid');
            $table->string('im_user_id')->nullable()->comment('IM provider returned user id');
            $table->json('extra')->nullable()->comment('extra/provider-specific data');
            $table->timestamps();

            $table->index('user_id');
            $table->index('user_identity_id');
            $table->unique(['provider', 'im_user_id']);
            $table->unique(['provider', 'external_user_id']);
            $table->comment('用户IM账号表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('rc_user_ims');
    }
};
