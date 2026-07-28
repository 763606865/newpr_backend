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
        Schema::table('rc_asset_ledgers', function (Blueprint $table): void {
            $table->unique('biz_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_asset_ledgers', function (Blueprint $table): void {
            $table->dropUnique(['biz_no']);
        });
    }
};
