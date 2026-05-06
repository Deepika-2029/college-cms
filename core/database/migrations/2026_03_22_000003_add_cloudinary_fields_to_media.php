<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'cloudinary_public_id')) {
                // public_id is needed to delete from Cloudinary (URL alone is not enough)
                $table->string('cloudinary_public_id', 255)->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('media', 'driver')) {
                // Track which driver stored this file: 'local' or 'cloudinary'
                $table->string('driver', 20)->default('local')->after('cloudinary_public_id');
            }
        });

        // Backfill: set driver='cloudinary' for existing rows that have a cloud URL
        \Illuminate\Support\Facades\DB::statement("
            UPDATE media
            SET driver = 'cloudinary'
            WHERE file_path LIKE 'http%cloudinary%'
               OR file_path LIKE 'https://res.cloudinary.com%'
        ");
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['cloudinary_public_id', 'driver']);
        });
    }
};
