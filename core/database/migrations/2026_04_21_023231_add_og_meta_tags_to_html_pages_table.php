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
            if (!Schema::hasColumn('html_pages', 'meta_keywords')) {
                $table->string('meta_keywords', 500)->nullable()->after('meta_description');
            }
            if (!Schema::hasColumn('html_pages', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('meta_keywords');
            }
            if (!Schema::hasColumn('html_pages', 'og_title')) {
                $table->string('og_title', 255)->nullable()->after('canonical_url');
            }
            if (!Schema::hasColumn('html_pages', 'og_description')) {
                $table->string('og_description', 500)->nullable()->after('og_title');
            }
            if (!Schema::hasColumn('html_pages', 'og_image')) {
                $table->string('og_image', 500)->nullable()->after('og_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('html_pages', function (Blueprint $table) {
            if (Schema::hasColumn('html_pages', 'meta_keywords')) {
                $table->dropColumn('meta_keywords');
            }
            if (Schema::hasColumn('html_pages', 'canonical_url')) {
                $table->dropColumn('canonical_url');
            }
            if (Schema::hasColumn('html_pages', 'og_title')) {
                $table->dropColumn('og_title');
            }
            if (Schema::hasColumn('html_pages', 'og_description')) {
                $table->dropColumn('og_description');
            }
            if (Schema::hasColumn('html_pages', 'og_image')) {
                $table->dropColumn('og_image');
            }
        });
    }
};
