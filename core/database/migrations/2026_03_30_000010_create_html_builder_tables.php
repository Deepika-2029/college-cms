<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── HTML Templates ────────────────────────────────────────────
        if (!Schema::hasTable('html_templates')) {
            Schema::create('html_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('category')->default('general');
                $table->longText('html')->nullable();
                $table->longText('css')->nullable();
                $table->longText('js')->nullable();
                $table->string('thumbnail')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // ── HTML Components ───────────────────────────────────────────
        if (!Schema::hasTable('html_components')) {
            Schema::create('html_components', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('category')->default('general');
                $table->longText('html')->nullable();
                $table->longText('css')->nullable();
                $table->longText('js')->nullable();
                $table->string('icon')->default('🧩');
                $table->string('thumbnail')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // ── HTML Pages (links template + stores overrides) ────────────
        if (!Schema::hasTable('html_pages')) {
            Schema::create('html_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('template_id')->nullable();
                // The base HTML (template html, or custom html if no template)
                $table->longText('base_html')->nullable();
                $table->longText('base_css')->nullable();
                $table->longText('base_js')->nullable();
                // Content overrides: JSON map of { cmsId => value }
                $table->longText('overrides')->nullable();
                // Components inserted: JSON array of { position, html, css, js }
                $table->longText('components')->nullable();
                $table->string('meta_description')->nullable();
                $table->string('og_image')->nullable();
                $table->enum('status', ['draft','published'])->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('html_pages');
        Schema::dropIfExists('html_components');
        Schema::dropIfExists('html_templates');
    }
};
