<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin account ─────────────────────────────────────────────
        // Default credentials — CHANGE PASSWORD after first login
        DB::table('users')->insertOrIgnore([
            'name'       => 'Admin',
            'email'      => 'admin@gpnainital.com',
            'password'   => Hash::make('ChangeMe@123'),
            'role'       => 'super_admin',
            'status'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Default navigation ────────────────────────────────────────
        $navFile = public_path('data/navigation.json');
        if (! file_exists($navFile)) {
            @mkdir(dirname($navFile), 0755, true);
            @file_put_contents($navFile, json_encode([
                'topbar'   => [],
                'mainnav'  => [
                    ['title' => 'Home',    'url' => '/'],
                    ['title' => 'About',   'url' => '/about'],
                    ['title' => 'Contact', 'url' => '/contact'],
                ],
                'menu'     => [
                    ['title' => 'Home',    'url' => '/'],
                    ['title' => 'About',   'url' => '/about'],
                    ['title' => 'Contact', 'url' => '/contact'],
                ],
                'footernav' => [],
                'settings'  => [
                    'site_name'      => 'My College',
                    'tagline'        => '',
                    'sticky'         => 'true',
                    'bg_color'       => '#1e293b',
                    'topbar_bg'      => '#0f172a',
                    'accent_color'   => '#6366f1',
                    'text_color'     => '#ffffff',
                    'show_admin_btn' => 'true',
                    'admin_btn_text' => 'Admin',
                    'btn1_text'      => '',
                    'btn1_url'       => '',
                    'btn1_style'     => 'primary',
                    'btn2_text'      => '',
                    'btn2_url'       => '',
                    'btn2_style'     => 'outline',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $this->command->newLine();
        $this->command->line('──────────────────────────────────────────────');
        $this->command->info('CMS ready!');
        $this->command->line('  Admin:    /admin');
        $this->command->line('  Email:    admin@gpnainital.com');
        $this->command->line('  Password: ChangeMe@123');
        $this->command->warn('  ⚠ Change your password immediately after login!');
        $this->command->line('──────────────────────────────────────────────');
    }
}
