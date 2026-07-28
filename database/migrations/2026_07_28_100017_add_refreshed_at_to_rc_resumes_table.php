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
        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->dateTime('refreshed_at')
                ->nullable()
                ->after('status')
                ->comment('最近一次权益刷新时间')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->dropColumn('refreshed_at');
        });
    }
};
