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
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('auditor_id')
                ->nullable()
                ->comment('审批人ID（admin_users.id）')
                ->after('id')
                ->index();
        });

        Schema::create('company_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->comment('企业ID')->index();
            $table->nullableMorphs('operator', 'company_operation_logs_operator_index');
            $table->string('action', 64)->comment('操作类型编码');
            $table->string('summary')->nullable()->comment('操作摘要');
            $table->json('changes')->nullable()->comment('变更详情（如 before/after）');
            $table->string('ip', 45)->nullable()->comment('操作IP');
            $table->text('user_agent')->nullable()->comment('User-Agent');
            $table->json('extra')->nullable()->comment('扩展字段');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index(['company_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->comment('企业运营操作日志表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_operation_logs');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('auditor_id');
        });
    }
};
