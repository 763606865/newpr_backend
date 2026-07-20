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
        Schema::table('rc_jobs', function (Blueprint $table): void {
            $table->decimal('annual_salary_months', 4, 1)
                ->nullable()
                ->after('salary_unit')
                ->comment('年薪月数（如13薪、14.5薪）');
        });

        Schema::table('schools', function (Blueprint $table): void {
            $table->string('official_logo')
                ->nullable()
                ->after('name')
                ->comment('学校官方 Logo OSS 路径');
        });

        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->text('personal_advantage')
                ->nullable()
                ->after('avatar')
                ->comment('个人优势');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rc_jobs', function (Blueprint $table): void {
            $table->dropColumn('annual_salary_months');
        });

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('official_logo');
        });

        Schema::table('rc_resumes', function (Blueprint $table): void {
            $table->dropColumn('personal_advantage');
        });
    }
};
