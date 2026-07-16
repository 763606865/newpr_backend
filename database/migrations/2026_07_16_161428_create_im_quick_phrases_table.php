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
        Schema::create('im_quick_phrases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_im_id')->comment('关联 rc_user_ims.id');
            $table->string('title', 64)->nullable()->comment('短语标题，便于后台管理');
            $table->text('content')->comment('快捷短语内容');
            $table->unsignedInteger('sort')->default(0)->comment('排序值，越大越靠前');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->unsignedInteger('used_count')->default(0)->comment('使用次数');
            $table->timestamp('last_used_at')->nullable()->comment('最后使用时间');
            $table->timestamps();

            $table->index(['user_im_id', 'is_enabled', 'sort'], 'im_quick_phrases_user_enabled_sort_idx');
            $table->index('last_used_at', 'im_quick_phrases_last_used_idx');
            $table->comment('IM 常用快捷短语表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('im_quick_phrases');
    }
};
