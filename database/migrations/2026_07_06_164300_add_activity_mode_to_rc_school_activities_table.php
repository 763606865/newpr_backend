<?php

use App\Enums\RcSchoolActivityMode;
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
        Schema::table('rc_school_activities', function (Blueprint $table): void {
            $table->unsignedTinyInteger('activity_mode')
                ->default(RcSchoolActivityMode::Offline->value)
                ->comment('活动模式：1-线上；2-线下')
                ->after('contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_school_activities', function (Blueprint $table): void {
            $table->dropColumn('activity_mode');
        });
    }
};
