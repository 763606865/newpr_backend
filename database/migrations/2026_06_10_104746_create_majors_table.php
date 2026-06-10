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
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('full_code', 30)->unique()->comment('专业国标编码');
            $table->string('name', 180)->comment('专业名称');
            $table->tinyInteger('level')->unsigned()->comment('层级：1大类 2专业类 3专业');
            $table->string('parent_code', 30)->nullable()->comment('父级编码，顶级为null');
            $table->string('type', 20)->comment('学历类型：中职/高职专科/职教本科');
            $table->string('tag', 20)->default('')->comment('扩展标签');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('1启用 0禁用');
            $table->timestamps();

            $table->index('parent_code');
            $table->index('level');
            $table->index('type');
            $table->comment('专业表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('majors');
    }
};
