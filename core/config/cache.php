<?php

use Illuminate\Support\Str;

return [

    'default' => env('CACHE_STORE', 'file'),

    'stores' => [

        'array' => [
            'driver'    => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path'   => env('CACHE_FILE_PATH', storage_path('framework/cache/data')),
            'lock_path' => env('CACHE_FILE_PATH', storage_path('framework/cache/data')),
        ],

        'redis' => [
            'driver'     => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

    ],

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_cache_'),

];
