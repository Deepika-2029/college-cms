<?php

namespace App\Filesystem;

use Illuminate\Filesystem\Filesystem;

/**
 * NoLockFilesystem
 *
 * Overrides put() to skip LOCK_EX.
 * Needed on Android/Termux (FAT32/exFAT) where flock() is unsupported.
 * Registered conditionally in AppServiceProvider — only on non-locking FSes.
 */
class NoLockFilesystem extends Filesystem
{
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, 0) !== false;
    }
}
