<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap\Deploy;

use Carbon\Carbon;
use Flarum\Http\UrlGenerator;
use FoF\Sitemap\Jobs\TriggerBuildJob;
use Illuminate\Contracts\Filesystem\Cloud;

class ProxyDisk implements DeployInterface
{
    public function __construct(
        public Cloud $sitemapStorage,
        public Cloud $indexStorage,
        private UrlGenerator $urlGenerator
    ) {
    }

    public function storeSet($setIndex, string $set): ?StoredSet
    {
        $path = "sitemap-$setIndex.xml";

        $this->sitemapStorage->put($path, $set);

        // Return main domain URL instead of storage URL
        return new StoredSet(
            $this->urlGenerator->to('forum')->route('fof-sitemap-set', ['id' => $setIndex]),
            Carbon::now()
        );
    }

    public function storeIndex(string $index): ?string
    {
        $this->indexStorage->put('sitemap.xml', $index);

        // Return main domain URL
        return $this->urlGenerator->to('forum')->route('fof-sitemap-index');
    }

    public function getIndex(): ?string
    {
        $logger = resolve('log');
        
        if (!$this->indexStorage->exists('sitemap.xml')) {
            $logger->debug('[FoF Sitemap] ProxyDisk: Index not found in remote storage, triggering build job');
            resolve('flarum.queue.connection')->push(new TriggerBuildJob());
            return null;
        }

        $logger->debug('[FoF Sitemap] ProxyDisk: Serving index from remote storage');
        return $this->indexStorage->get('sitemap.xml');
    }

    public function getSet($setIndex): ?string
    {
        $logger = resolve('log');
        $path = "sitemap-$setIndex.xml";
        
        if (!$this->sitemapStorage->exists($path)) {
            $logger->debug("[FoF Sitemap] ProxyDisk: Set $setIndex not found in remote storage");
            return null;
        }

        $logger->debug("[FoF Sitemap] ProxyDisk: Serving set $setIndex from remote storage");
        return $this->sitemapStorage->get($path);
    }
}
