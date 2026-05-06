<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });

            try {
                $jsonFile = storage_path('app/cms_settings.json');
                if (file_exists($jsonFile)) {
                    $jsonContent = file_get_contents($jsonFile);
                    if ($jsonContent) {
                        $data = json_decode($jsonContent, true);
                        if (is_array($data)) {
                            foreach ($data as $k => $v) {
                                if (is_array($v) || is_object($v)) {
                                    $v = json_encode($v);
                                }
                                DB::table('settings')->insert([
                                    'key' => $k,
                                    'value' => $v,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Settings Migration Failed: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
