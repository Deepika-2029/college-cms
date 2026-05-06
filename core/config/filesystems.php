<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => true,
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

    ],

    // Storage symlink: public_html/storage → cms/storage/app/public
    // Run: php artisan storage:link  (from cms/ directory)
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
