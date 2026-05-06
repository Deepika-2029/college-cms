<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * College CMS — Single consolidated migration.
 * Creates ALL tables in the correct dependency order.
 * Safe to run on a fresh database; each table is guarded with hasTable().
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. users ──────────────────────────────────────────────────────
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->enum('role', ['super_admin', 'admin'])->default('super_admin');
                $table->string('avatar', 255)->nullable();
                $table->text('bio')->nullable();
                $table->boolean('status')->default(true);
                $table->string('department', 150)->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // ── 2. sessions ───────────────────────────────────────────────────
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // ── 3. password_reset_tokens ──────────────────────────────────────
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // ── 4. tables_registry ────────────────────────────────────────────
        if (! Schema::hasTable('tables_registry')) {
            Schema::create('tables_registry', function (Blueprint $table) {
                $table->id();
                $table->string('table_name')->unique();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // ── 5. media ──────────────────────────────────────────────────────
        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->string('file_path');
                $table->string('cloudinary_public_id', 255)->nullable(); // For Cloudinary delete
                $table->string('driver', 20)->default('local');           // 'local' | 'cloudinary'
                $table->string('title')->nullable();
                $table->string('alt', 255)->nullable();
                $table->string('caption', 500)->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('tags')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }


        // ── 6. audit_logs ─────────────────────────────────────────────────
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->string('user_email')->nullable();
                $table->string('user_role', 20)->nullable();
                $table->string('action', 60);
                $table->string('target_type', 60)->nullable();
                $table->string('target_id')->nullable();
                $table->string('target_label')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('country', 80)->nullable();
                $table->string('city', 80)->nullable();
                $table->boolean('is_suspicious')->default(false);
                $table->string('suspicious_reason', 255)->nullable();
                $table->boolean('blocked')->default(false);
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['action', 'created_at']);
                $table->index('created_at');
                $table->index(['ip_address', 'action', 'created_at'], 'audit_ip_action_idx');
                $table->index(['user_email', 'action', 'created_at'], 'audit_email_action_idx');
                $table->index(['is_suspicious', 'created_at'], 'audit_suspicious_idx');
            });
        }

        // ── 7. blocked_ips ────────────────────────────────────────────────
        if (! Schema::hasTable('blocked_ips')) {
            Schema::create('blocked_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->unique();
                $table->string('reason', 255)->nullable();
                $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 8. login_attempts ─────────────────────────────────────────────
        if (! Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('email', 255)->index();
                $table->string('ip_address', 45)->index();
                $table->boolean('success')->default(false);
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('attempted_at')->useCurrent();
                $table->index(['email', 'ip_address', 'attempted_at']);
            });
        }

        // ── 9. pages ──────────────────────────────────────────────────────
        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->string('slug', 200)->unique();
                $table->enum('status', ['draft', 'published'])->default('published');
                $table->boolean('is_homepage')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'slug']);
            });
        }

        // ── 10. page_versions ─────────────────────────────────────────────
        if (! Schema::hasTable('page_versions')) {
            Schema::create('page_versions', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 200)->index();
                $table->string('title', 200);
                $table->json('rows')->nullable();
                $table->json('sections')->nullable();
                $table->string('global_css', 5000)->nullable();
                $table->enum('status', ['draft', 'published'])->default('published');
                $table->foreignId('saved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('version_note', 255)->nullable();
                $table->timestamps();
                $table->index(['slug', 'created_at']);
            });
        }

        // ── 11. templates ─────────────────────────────────────────────────
        if (! Schema::hasTable('templates')) {
            Schema::create('templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 200);
                $table->string('slug', 200)->unique();
                $table->text('description')->nullable();
                $table->string('category', 100)->default('General');
                $table->json('sections')->nullable();
                $table->json('rows')->nullable();
                $table->string('thumbnail')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['is_active', 'category']);
            });
        }

        // ── 14. dashboard_widgets ─────────────────────────────────────────
        if (! Schema::hasTable('dashboard_widgets')) {
            Schema::create('dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->string('type', 50)->nullable(); // stat, table_count, recent_records, custom_html, login_activity
                $table->string('table_name', 100)->nullable();
                $table->string('label_field', 100)->nullable();
                $table->string('color', 30)->default('#5a67d8');
                $table->string('icon', 50)->default('bi-bar-chart-fill');
                $table->integer('limit')->default(5);
                $table->text('custom_html')->nullable();
                $table->boolean('visible_to_admin')->default(true);
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ── 15. user_dashboard_widgets ────────────────────────────────────
        if (! Schema::hasTable('user_dashboard_widgets')) {
            Schema::create('user_dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('widget_id')->constrained('dashboard_widgets')->cascadeOnDelete();
                $table->boolean('enabled')->default(true);
                $table->integer('order')->default(0);
                $table->unique(['user_id', 'widget_id']);
                $table->timestamps();
            });
        }

        // ── 17. admin_ui_customizations ───────────────────────────────────
        if (! Schema::hasTable('admin_ui_customizations')) {
            Schema::create('admin_ui_customizations', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20); // 'css' or 'js'
                $table->text('content');
                $table->boolean('is_active')->default(true);
                $table->string('description', 200)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ── 18. user_ip_allowlists ────────────────────────────────────────
        if (! Schema::hasTable('user_ip_allowlists')) {
            Schema::create('user_ip_allowlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('ip_address', 45);
                $table->string('label', 100)->nullable();
                $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'ip_address']);
            });
        }

        // ── 19. api_keys ──────────────────────────────────────────────────
        if (! Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('key_hash');          // bcrypt hash — never store raw
                $table->string('key_prefix', 8)->nullable()->index(); // plaintext prefix for pre-filtering
                $table->string('table_name', 100);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('request_count')->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->index(['key_prefix', 'is_active']);
            });
        }

        // ── Seed: default dashboard widgets ───────────────────────────────
        if (DB::table('dashboard_widgets')->count() === 0) {
            $widgets = [
                ['name' => 'My Activity Count', 'slug' => 'my-activity',   'type' => 'stat',           'color' => '#5a67d8', 'icon' => 'bi-activity',      'order' => 1],
                ['name' => 'My Media Uploads',  'slug' => 'my-media',      'type' => 'stat',           'color' => '#8b5cf6', 'icon' => 'bi-images',        'order' => 2],
                ['name' => 'Login History',     'slug' => 'login-history', 'type' => 'login_activity', 'color' => '#0ea5e9', 'icon' => 'bi-clock-history', 'order' => 3],
                ['name' => 'Recent Actions',    'slug' => 'recent-actions','type' => 'recent_records', 'color' => '#10b981', 'icon' => 'bi-list-check',    'order' => 4],
            ];
            foreach ($widgets as $w) {
                DB::table('dashboard_widgets')->insert(array_merge($w, [
                    'table_name'       => null,
                    'label_field'      => null,
                    'limit'            => 5,
                    'custom_html'      => null,
                    'visible_to_admin' => true,
                    'is_active'        => true,
                    'created_by'       => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        // Drop in reverse dependency order
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('user_ip_allowlists');
        Schema::dropIfExists('admin_ui_customizations');
        Schema::dropIfExists('admin_menu_items');
        Schema::dropIfExists('user_dashboard_widgets');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('plugin_blueprints');
        Schema::dropIfExists('plugin_states');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('media');
        Schema::dropIfExists('tables_registry');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
