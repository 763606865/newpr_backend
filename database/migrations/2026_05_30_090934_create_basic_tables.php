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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->comment('名称');
            $table->string('code', 32)->comment('行政区划代码')->unique();
            $table->string('parent_code', 32)->nullable()->comment('父级code');
            $table->tinyInteger('level')->comment('1省 2市 3区县');
            $table->string('type', 32)->nullable()->comment('类型');
            $table->timestamps();
            $table->comment('全国行政区划表');
        });
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('school_code', 30)->nullable()->comment('学校代码');
            $table->string('name', 100)->comment('学校名称');
            $table->string('province', 30)->nullable()->comment('省');
            $table->string('city', 30)->nullable()->comment('市');
            $table->string('area', 30)->nullable()->comment('区/县');
            $table->string('address', 200)->nullable()->comment('地址');
            $table->string('competent_dept', 50)->nullable()->comment('主管部门');
            $table->string('type', 20)->nullable()->comment('类型 本科/专科/高中/小学');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->comment('学校表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
        Schema::dropIfExists('areas');
    }
};
