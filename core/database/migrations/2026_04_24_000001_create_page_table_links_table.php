<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('page_table_links', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug', 120)->index();
            $table->string('table_name', 64)->index();
            $table->timestamps();
            $table->unique(['page_slug', 'table_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_table_links');
    }
};
