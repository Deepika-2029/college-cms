<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * College CMS v3 — Additive migration.
 * Adds new fields/tables needed by v3 features.
 * Safe to run on existing v2 databases (all guarded with hasColumn/hasTable).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── media: add folder support ─────────────────────────────────────
        if (Schema::hasTable('media')) {
            Schema::table('media', function (Blueprint $table) {
                if (!Schema::hasColumn('media', 'folder')) {
                    $table->string('folder', 100)->nullable()->default(null)->after('tags');
                    $table->index('folder');
                }
                if (!Schema::hasColumn('media', 'description')) {
                    $table->text('description')->nullable()->after('caption');
                }
            });
        }

        // ── media_folders ─────────────────────────────────────────────────
        if (!Schema::hasTable('media_folders')) {
            Schema::create('media_folders', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('order')->default(0);
                $table->timestamps();
                $table->index(['parent_id', 'order']);
            });
        }

        // ── pages: store detected template variables ───────────────────────
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                if (!Schema::hasColumn('pages', 'detected_variables')) {
                    $table->json('detected_variables')->nullable()->after('is_homepage');
                }
                if (!Schema::hasColumn('pages', 'meta_description')) {
                    $table->string('meta_description', 500)->nullable()->after('detected_variables');
                }
                if (!Schema::hasColumn('pages', 'og_image')) {
                    $table->string('og_image', 500)->nullable()->after('meta_description');
                }
            });
        }

        // ── plugin_blueprints: add ai_import flag ─────────────────────────
        if (Schema::hasTable('plugin_blueprints')) {
            Schema::table('plugin_blueprints', function (Blueprint $table) {
                if (!Schema::hasColumn('plugin_blueprints', 'ai_import')) {
                    $table->boolean('ai_import')->default(false)->after('published');
                }
                if (!Schema::hasColumn('plugin_blueprints', 'detected_variables')) {
                    $table->json('detected_variables')->nullable()->after('ai_import');
                }
            });
        }

        // ── templates: add thumbnail_data (base64 preview) ────────────────
        if (Schema::hasTable('templates')) {
            Schema::table('templates', function (Blueprint $table) {
                if (!Schema::hasColumn('templates', 'preview_html')) {
                    $table->text('preview_html')->nullable()->after('thumbnail');
                }
                if (!Schema::hasColumn('templates', 'detected_variables')) {
                    $table->json('detected_variables')->nullable()->after('preview_html');
                }
            });
        }

        // ── ai_import_log ─────────────────────────────────────────────────
        if (!Schema::hasTable('ai_import_log')) {
            Schema::create('ai_import_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 30)->default('import'); // import | export | save_plugin
                $table->string('plugin_id', 100)->nullable();
                $table->json('variables')->nullable();
                $table->json('warnings')->nullable();
                $table->json('blocked')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_import_log');
        Schema::dropIfExists('media_folders');

        if (Schema::hasTable('media')) {
            Schema::table('media', function (Blueprint $table) {
                if (Schema::hasColumn('media', 'folder'))      $table->dropColumn('folder');
                if (Schema::hasColumn('media', 'description')) $table->dropColumn('description');
            });
        }
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                if (Schema::hasColumn('pages', 'detected_variables')) $table->dropColumn('detected_variables');
                if (Schema::hasColumn('pages', 'meta_description'))   $table->dropColumn('meta_description');
                if (Schema::hasColumn('pages', 'og_image'))           $table->dropColumn('og_image');
            });
        }
    }
};
