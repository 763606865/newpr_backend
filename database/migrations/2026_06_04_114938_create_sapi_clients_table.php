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
        Schema::create('sapi_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('客户端名称');
            $table->string('app_key', 64)->unique()->comment('应用 Key');
            $table->text('app_secret')->comment('应用 Secret（加密存储）');
            $table->tinyInteger('status')->default(1)->comment('状态: 1-启用, 0-停用')->index();
            $table->json('allowed_ips')->nullable()->comment('允许访问的 IP 白名单，空表示不限制');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('SApi 接入客户端表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sapi_clients');
    }
};
