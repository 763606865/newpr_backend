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
        Schema::table('im_conversations', function (Blueprint $table) {
            $table->nullableMorphs('context', 'im_conversations_context_index', 'owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('im_conversations', function (Blueprint $table) {
            $table->dropMorphs('context', 'im_conversations_context_index');
        });
    }
};
