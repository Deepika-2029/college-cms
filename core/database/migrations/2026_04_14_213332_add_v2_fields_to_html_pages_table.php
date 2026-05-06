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
        Schema::table('html_pages', function (Blueprint $table) {
            // V2 Canvas Builder: stores Fabric.js scene JSON (multi-page)
            $table->longText('scene_json')->nullable()->after('base_js')->comment('Fabric.js scene JSON for V2 canvas builder');
            // 1 = V1 DOM Builder (default), 2 = V2 Canvas Builder
            $table->tinyInteger('builder_version')->default(1)->after('scene_json')->comment('1=DOM builder, 2=Canvas builder');
        });
    }

    public function down(): void
    {
        Schema::table('html_pages', function (Blueprint $table) {
            $table->dropColumn(['scene_json', 'builder_version']);
        });
    }
};
