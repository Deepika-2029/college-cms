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
            if (!Schema::hasColumn('html_pages', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('html_pages', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable()->after('meta_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('html_pages', function (Blueprint $table) {
            if (Schema::hasColumn('html_pages', 'meta_title')) {
                $table->dropColumn('meta_title');
            }
            if (Schema::hasColumn('html_pages', 'meta_keywords')) {
                $table->dropColumn('meta_keywords');
            }
        });
    }
};
