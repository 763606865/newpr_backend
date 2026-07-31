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
        Schema::create('contact_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('姓名或称呼');
            $table->string('phone', 20)->comment('联系电话');
            $table->string('company_name', 150)->nullable()->comment('公司名称');
            $table->string('source')->nullable()->comment('来源');
            $table->unsignedTinyInteger('product')->comment('咨询产品');
            $table->text('content')->comment('申请或咨询内容');
            $table->unsignedTinyInteger('status')->default(0)->comment('回访状态：0-待回访，1-已回访');
            $table->unsignedBigInteger('follow_up_admin_user_id')->nullable()->comment('跟进运营人员ID');
            $table->text('follow_up_note')->nullable()->comment('跟进备注');
            $table->timestamp('submitted_at')->useCurrent()->comment('申请提交时间');
            $table->timestamp('followed_up_at')->nullable()->comment('回访时间');
            $table->string('ip', 45)->nullable()->comment('提交IP');
            $table->string('user_agent', 500)->nullable()->comment('提交端User-Agent');
            $table->json('extra')->nullable()->comment('扩展信息');
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('follow_up_admin_user_id');
            $table->index('followed_up_at');
            $table->index(['status', 'submitted_at']);
            $table->index(['product', 'status']);
            $table->comment('C端联系我们申请表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_inquiries');
    }
};
