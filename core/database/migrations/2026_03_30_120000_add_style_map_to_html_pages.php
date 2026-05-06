<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('html_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('html_pages', 'style_map')) {
                $table->json('style_map')->nullable()->after('components')
                    ->comment('Per-device style overrides from the visual builder style engine');
            }
        });
    }

    public function down(): void
    {
        Schema::table('html_pages', function (Blueprint $table) {
            $table->dropColumn('style_map');
        });
    }
};
