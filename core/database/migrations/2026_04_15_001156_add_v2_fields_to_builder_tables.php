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
        Schema::table('html_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('html_templates', 'scene_json')) {
                $table->longText('scene_json')->nullable()->after('html');
                $table->tinyInteger('builder_version')->default(1)->after('scene_json')->comment('1=DOM builder, 2=Canvas builder');
            }
        });
        Schema::table('html_components', function (Blueprint $table) {
            if (!Schema::hasColumn('html_components', 'scene_json')) {
                $table->longText('scene_json')->nullable()->after('html');
                $table->tinyInteger('builder_version')->default(1)->after('scene_json')->comment('1=DOM builder, 2=Canvas builder');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('html_templates', function (Blueprint $table) {
            $table->dropColumn(['scene_json', 'builder_version']);
        });
        Schema::table('html_components', function (Blueprint $table) {
            $table->dropColumn(['scene_json', 'builder_version']);
        });
    }
};
