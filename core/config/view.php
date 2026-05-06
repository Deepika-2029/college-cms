<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    | On Android/Termux, VIEW_COMPILED_PATH is set by entry.php to a
    | directory on internal storage (sys_get_temp_dir()) that supports
    | file locking. On normal servers, uses the standard storage path.
    */
    'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views')),
];
