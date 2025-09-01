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
use Psr\Log\LoggerInterface;

class Disk implements DeployInterface
{
    public function __construct(
        public Cloud $sitemapStorage,
        public Cloud $indexStorage,
        protected UrlGenerator $url,
        protected LoggerInterface $logger
    ) {
    }

    public function storeSet($setIndex, string $set): ?StoredSet
    {
        $path = "sitemap-$setIndex.xml";

        $this->sitemapStorage->put($path, $set);

        return new StoredSet(
            $this->url->to('forum')->route('fof-sitemap-set', ['id' => $setIndex]),
            Carbon::now()
        );
    }

    public function storeIndex(string $index): ?string
    {
        $this->indexStorage->put('sitemap.xml', $index);

        return $this->url->to('forum')->route('fof-sitemap-index');
    }

    public function getIndex(): ?string
    {
        if (!$this->indexStorage->exists('sitemap.xml')) {
            $this->logger->debug('[FoF Sitemap] Disk: Index not found, triggering build job');
            resolve('flarum.queue.connection')->push(new TriggerBuildJob());

            return null;
        }

        $this->logger->debug('[FoF Sitemap] Disk: Serving index from local storage');

        return $this->indexStorage->get('sitemap.xml');
    }

    public function getSet($setIndex): ?string
    {
        $path = "sitemap-$setIndex.xml";

        if (!$this->sitemapStorage->exists($path)) {
            $this->logger->debug("[FoF Sitemap] Disk: Set $setIndex not found in local storage");

            return null;
        }

        $this->logger->debug("[FoF Sitemap] Disk: Serving set $setIndex from local storage");

        return $this->sitemapStorage->get($path);
    }
}
