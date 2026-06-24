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
        Schema::create('cms_menu_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->comment('菜单ID')->constrained('cms_menus')->cascadeOnDelete();
            $table->unsignedTinyInteger('identity_type')->comment('可见身份：0-游客；1-求职者；2-招聘方；3-校招负责人；4-政府机构负责人；5-猎头');
            $table->timestamps();

            $table->unique(['menu_id', 'identity_type']);
            $table->index('identity_type');
            $table->comment('门户菜单可见身份关联表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_menu_identities');
    }
};
