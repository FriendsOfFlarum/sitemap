<?php

namespace FoF\Sitemap\Deploy;

use Carbon\Carbon;
use FoF\Sitemap\Sitemap\UrlSet;
use Illuminate\Contracts\Filesystem\Cloud;

class Disk implements DeployInterface
{
    public function __construct(
        public Cloud $sitemapStorage,
        public Cloud $indexStorage
    ) {}

    public function storeSet($setIndex, string $set): ?StoredSet
    {
        $path = "sitemap-$setIndex.xml";

        $this->sitemapStorage->put($path, $set);

        return new StoredSet(
            $this->sitemapStorage->url($path),
            Carbon::now()
        );
    }

    public function storeIndex(string $index): ?string
    {
        $this->indexStorage->put('sitemap.xml', $index);

        return $this->indexStorage->url('sitemap.xml');
    }

    public function getIndex(): ?string
    {
        return $this->indexStorage->url('sitemap.xml');
    }
}
