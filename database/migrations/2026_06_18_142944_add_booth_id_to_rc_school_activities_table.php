<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rc_school_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('booth_id')
                ->nullable()
                ->after('organizer_id')
                ->comment('采用的展位模板ID rc_school_booths.id')
                ->index();
        });

        DB::table('rc_school_activities')
            ->whereNull('booth_id')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $activityId): void {
                $boothId = DB::table('rc_school_activity_booths')
                    ->where('activity_id', $activityId)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->value('booth_id');

                if ($boothId !== null) {
                    DB::table('rc_school_activities')
                        ->where('id', $activityId)
                        ->update(['booth_id' => $boothId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_school_activities', function (Blueprint $table) {
            $table->dropIndex(['booth_id']);
            $table->dropColumn('booth_id');
        });
    }
};
