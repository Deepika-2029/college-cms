<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // Display name, e.g. "Feature Card"
            $table->string('slug')->unique();  // class/type key, e.g. "feature-card"
            $table->string('category')->default('custom'); // for grouping in left panel
            $table->text('html_template');     // HTML with {{variable}} placeholders
            $table->text('css')->nullable();   // scoped CSS
            $table->text('js')->nullable();    // optional JS
            $table->json('schema_json');       // {"fields":[{"type":"text","key":"title","label":"Title","default":""},...]}
            $table->string('icon')->default('🧩'); // emoji or SVG string for left panel
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_components');
    }
};
