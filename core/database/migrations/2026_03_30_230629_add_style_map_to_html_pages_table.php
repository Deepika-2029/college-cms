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
            if (!Schema::hasColumn('html_pages', 'style_map')) {
                $table->longText('style_map')->nullable()->after('components');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('html_pages', function (Blueprint $table) {
            if (Schema::hasColumn('html_pages', 'style_map')) {
                $table->dropColumn('style_map');
            }
        });
    }
};
