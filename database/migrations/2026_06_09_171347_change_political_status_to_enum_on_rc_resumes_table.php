<?php

use App\Enums\RcPoliticalStatus;
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
        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('political_status_new')
                ->default(RcPoliticalStatus::Masses->value)
                ->after('marital_status')
                ->comment('政治面貌: 1-中共党员（含预备党员）, 2-民主党派, 3-无党派人士, 4-团员, 5-群众');
        });

        $labelMap = [
            '中共党员（含预备党员）' => RcPoliticalStatus::CpcMember->value,
            '中共党员' => RcPoliticalStatus::CpcMember->value,
            '预备党员' => RcPoliticalStatus::CpcMember->value,
            '民主党派' => RcPoliticalStatus::DemocraticParty->value,
            '无党派人士' => RcPoliticalStatus::NonPartisan->value,
            '团员' => RcPoliticalStatus::LeagueMember->value,
            '群众' => RcPoliticalStatus::Masses->value,
        ];

        DB::table('rc_resumes')
            ->select(['id', 'political_status'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($labelMap): void {
                foreach ($rows as $row) {
                    $raw = trim((string) $row->political_status);
                    $value = is_numeric($raw)
                        ? RcPoliticalStatus::tryFrom((int) $raw)?->value
                        : ($labelMap[$raw] ?? null);

                    DB::table('rc_resumes')
                        ->where('id', $row->id)
                        ->update([
                            'political_status_new' => $value ?? RcPoliticalStatus::Masses->value,
                        ]);
                }
            });

        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->dropColumn('political_status');
        });

        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->renameColumn('political_status_new', 'political_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->string('political_status_old', 20)
                ->default('群众')
                ->after('marital_status')
                ->comment('政治面貌');
        });

        $valueMap = [
            RcPoliticalStatus::CpcMember->value => '中共党员（含预备党员）',
            RcPoliticalStatus::DemocraticParty->value => '民主党派',
            RcPoliticalStatus::NonPartisan->value => '无党派人士',
            RcPoliticalStatus::LeagueMember->value => '团员',
            RcPoliticalStatus::Masses->value => '群众',
        ];

        DB::table('rc_resumes')
            ->select(['id', 'political_status'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($valueMap): void {
                foreach ($rows as $row) {
                    DB::table('rc_resumes')
                        ->where('id', $row->id)
                        ->update([
                            'political_status_old' => $valueMap[(int) $row->political_status] ?? '群众',
                        ]);
                }
            });

        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->dropColumn('political_status');
        });

        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->renameColumn('political_status_old', 'political_status');
        });
    }
};
