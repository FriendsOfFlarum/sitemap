<?php

namespace FoF\Sitemap\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;

interface StorageInterface
{
    public function getIndex(): ?array;

    public function publish(array $sitemaps): array;

    public function flush(): void;

    public static function setTemporaryFilesystem(Filesystem $filesystem): void;
}
