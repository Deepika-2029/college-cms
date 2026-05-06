<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HtmlPage;
use Illuminate\Support\Facades\File;

class MigrateOldPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:migrate-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old JSON/HTML split directory pages into the SQL html_pages table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pagesDir = public_path('pages');
        $dataDir  = public_path('data/pages');

        if (!File::exists($pagesDir) || !File::exists($dataDir)) {
            $this->error('Old pages directories not found. Continuing...');
            return;
        }

        $directories = File::directories($pagesDir);
        $count = 0;

        foreach ($directories as $dir) {
            $slug = basename($dir);
            $htmlPath = $dir . '/index.html';
            $cssPath  = $dir . '/style.css';
            $jsPath   = $dir . '/script.js';
            $jsonPath = $dataDir . '/' . $slug . '/page.json';

            if (!File::exists($htmlPath) || !File::exists($jsonPath)) {
                $this->warn("Skipping $slug: Missing index.html or page.json");
                continue;
            }

            $jsonData = json_decode(File::get($jsonPath), true);
            $title    = $jsonData['title'] ?? ucfirst(str_replace('-', ' ', $slug));
            $status   = $jsonData['status'] ?? 'draft';

            // Check if it already exists
            $page = HtmlPage::where('slug', $slug)->first();
            
            if ($page) {
                // If it exists, skip or update? Let's skip and preserve any new edits.
                $this->line("Skipping $slug: Already exists in database.");
                continue;
            }

            $page = new HtmlPage();
            $page->title = $title;
            $page->slug  = $slug;
            $page->status = $status;
            
            // Read contents
            $page->base_html = File::get($htmlPath);
            if (File::exists($cssPath)) {
                $page->base_css = File::get($cssPath);
            }
            if (File::exists($jsPath)) {
                $page->base_js = File::get($jsPath);
            }

            // Since this is a legacy page, style_map and tree will initially be null.
            // When opened in Visual Builder, the auto-migration canvas.js logic will parse 
            // base_html into a JSON tree and push it back up!

            $page->save();

            $this->info("Successfully migrated page: $slug");
            $count++;
        }

        $this->info("Completed. Migrated $count legacy pages to the database.");
    }
}
