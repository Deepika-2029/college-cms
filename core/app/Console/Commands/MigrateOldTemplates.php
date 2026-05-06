<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HtmlTemplate;
use Illuminate\Support\Facades\File;

class MigrateOldTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:migrate-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old JSON/HTML split directory templates into the SQL html_templates table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templatesDir = resource_path('views/admin/visual-builder/templates/files');

        if (!File::exists($templatesDir)) {
            $this->error('Old templates directory not found. Continuing...');
            return;
        }

        $directories = File::directories($templatesDir);
        $count = 0;

        foreach ($directories as $dir) {
            $slug = basename($dir);
            $htmlPath = $dir . '/template.html';
            $cssPath  = $dir . '/style.css';
            $jsPath   = $dir . '/script.js';
            $jsonPath = $dir . '/config.json';
            
            // Allow fallback to index.html if template.html is missing
            if (!File::exists($htmlPath) && File::exists($dir . '/index.html')) {
                $htmlPath = $dir . '/index.html';
            }

            if (!File::exists($htmlPath)) {
                $this->warn("Skipping $slug: Missing template.html");
                continue;
            }

            $title = ucfirst(str_replace('-', ' ', $slug));
            $category = 'general';
            
            if (File::exists($jsonPath)) {
                $jsonData = json_decode(File::get($jsonPath), true);
                if ($jsonData && isset($jsonData['name'])) {
                    $title = $jsonData['name'];
                }
                if ($jsonData && isset($jsonData['category'])) {
                    $category = $jsonData['category'];
                }
            }

            // Check if it already exists
            $template = HtmlTemplate::where('slug', $slug)->first();
            
            if ($template) {
                $this->line("Skipping $slug: Already exists in database.");
                continue;
            }

            $template = new HtmlTemplate();
            $template->name = $title;
            $template->slug  = $slug;
            $template->category = $category;
            $template->is_active = true;
            
            // Read contents
            $template->html = File::get($htmlPath);
            if (File::exists($cssPath)) {
                $template->css = File::get($cssPath);
            }
            if (File::exists($jsPath)) {
                $template->js = File::get($jsPath);
            }

            $template->save();

            $this->info("Successfully migrated template: $slug");
            $count++;
        }

        $this->info("Completed. Migrated $count legacy templates to the database.");
    }
}
