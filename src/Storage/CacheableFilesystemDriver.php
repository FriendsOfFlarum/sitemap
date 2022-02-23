<?php

namespace FoF\Sitemap\Storage;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

class CacheableFilesystemDriver implements StorageInterface
{
    private static ?Filesystem $temporaryFilesystem = null;
    /**
     * @var Filesystem|Cloud
     */
    private Filesystem $filesystem;
    private ?Store $cache;

    const IndexKey = 'fof.sitemap.index';

    public function __construct(Filesystem $filesystem, Store $cache = null)
    {
        $this->filesystem = $filesystem;
        $this->cache = $cache;
    }

    public function getIndex(): ?array
    {
        if ($cached = $this->cache?->get(static::IndexKey)) {
            return $cached;
        }

        try {
            $stored = $this->filesystem->get(static::IndexKey);
        } catch (FileNotFoundException $_) {
            return null;
        }

        return json_decode($stored, true);
    }

    public function publish(array $sitemaps): array
    {
        $publish = [];

        foreach ($sitemaps as $sitemap => $lastModified) {
            $this->filesystem->put(
                basename($sitemap),
                static::getTemporaryFilesystem()->readStream($sitemap)
            );

            try {
                $publish[$sitemap] = $this->filesystem->url(basename($sitemap));
            } catch (RuntimeException $e) {}
        }

        $this->filesystem->put(static::IndexKey, json_encode($publish));

        $this->cache?->forever(static::IndexKey, $publish);

        return $publish;
    }

    public function flush(): void
    {
        foreach ($this->getIndex() ?? [] as $path => $url) {
            $this->filesystem->delete(basename($path));
        }

        $this->cache?->forget(static::IndexKey);
        $this->filesystem->delete(static::IndexKey);
    }

    public static function setTemporaryFilesystem(Filesystem $filesystem): void
    {
        static::$temporaryFilesystem = $filesystem;
    }

    public static function getTemporaryFilesystem(): Filesystem
    {
        return static::$temporaryFilesystem ?? resolve('fof.sitemap.tmp-disk');
    }

    public function url(string $path): string
    {
        return $this->filesystem->url($path);
    }
}
