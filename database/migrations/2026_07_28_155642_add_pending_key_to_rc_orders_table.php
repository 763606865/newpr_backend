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
        Schema::table('rc_orders', function (Blueprint $table) {
            $table->char('pending_key', 64)
                ->nullable()
                ->unique()
                ->after('order_no')
                ->comment('待支付订单幂等键，支付或取消后清空');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_orders', function (Blueprint $table) {
            $table->dropUnique(['pending_key']);
            $table->dropColumn('pending_key');
        });
    }
};
