<?php

namespace App\Session;

use Illuminate\Session\FileSessionHandler;
use Illuminate\Filesystem\Filesystem;

/**
 * NoLockFileSessionHandler
 *
 * Overrides only write() to skip LOCK_EX.
 * All other operations (read, destroy, gc) use the standard handler.
 *
 * Needed on Android/Termux where FAT32/exFAT storage doesn't support flock().
 * Error without this: "file_put_contents(): Exclusive locks are not supported"
 */
class NoLockFileSessionHandler extends FileSessionHandler
{
    public function __construct(Filesystem $files, string $path, int $minutes)
    {
        parent::__construct($files, $path, $minutes);
    }

    public function write($sessionId, $data): bool
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $sessionId;
        // Write without LOCK_EX — safe on single-process dev servers
        return @file_put_contents($path, $data) !== false;
    }
}
