<?php

namespace FoF\Sitemap\Disk;

use FoF\Sitemap\Storage\StorageInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;

abstract class Disk
{
    protected static ?Filesystem $temporaryFilesystem = null;

    protected static ?StorageInterface $targetFilesystem = null;

    public static function getTargetFilesystem(): ?StorageInterface
    {
        return self::$targetFilesystem ?? resolve('fof.sitemap.storage');
    }

    public static function setTemporaryFilesystem(Filesystem $filesystem): void
    {
        static::$temporaryFilesystem = $filesystem;
    }

    public static function getTemporaryFilesystem(): Filesystem
    {
        return static::$temporaryFilesystem ?? resolve('fof.sitemap.tmp-disk');
    }

    protected function gzip(string $path)
    {
        $fs = static::getTemporaryFilesystem();

        // Check gzip
        if (function_exists('gzencode')) {
            $fs->put(
                $path,
                gzencode($fs->get($path))
            );
        }
    }

    /**
     * Limit the number of entries to 50.000.
     *
     * @return array|string[]
     */
    public function write(): array
    {
        return $this->chunk();
    }

    protected function view(): Factory
    {
        return resolve(Factory::class);
    }
}
