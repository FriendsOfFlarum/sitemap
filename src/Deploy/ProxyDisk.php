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
use Illuminate\Contracts\Queue\Queue;
use Psr\Log\LoggerInterface;

class ProxyDisk implements DeployInterface, PrunesSets
{
    public function __construct(
        public Cloud $sitemapStorage,
        public Cloud $indexStorage,
        private UrlGenerator $urlGenerator,
        protected LoggerInterface $logger,
        protected Queue $queue,
    ) {
    }

    public function storeSet($setIndex, string $set): ?StoredSet
    {
        $path = "sitemap-$setIndex.xml";

        if (!$this->sitemapStorage->put($path, $set)) {
            throw new \RuntimeException('Unable to store sitemap set: '.$path);
        }

        // Return main domain URL instead of storage URL
        return new StoredSet(
            $this->urlGenerator->to('forum')->route('fof-sitemap-set', ['id' => $setIndex]),
            Carbon::now()
        );
    }

    public function storeIndex(string $index): ?string
    {
        if (!$this->indexStorage->put('sitemap.xml', $index)) {
            throw new \RuntimeException('Unable to publish sitemap index');
        }

        // Return main domain URL
        return $this->urlGenerator->to('forum')->route('fof-sitemap-index');
    }

    public function getIndex(): ?string
    {
        if (!$this->indexStorage->exists('sitemap.xml')) {
            $this->logger->debug('[FoF Sitemap] ProxyDisk: Index not found in remote storage, triggering build job');
            $this->queue->push(new TriggerBuildJob());

            return null;
        }

        $this->logger->debug('[FoF Sitemap] ProxyDisk: Serving index from remote storage');

        return $this->indexStorage->get('sitemap.xml');
    }

    public function getSet($setIndex): ?string
    {
        $path = "sitemap-$setIndex.xml";

        if (!$this->sitemapStorage->exists($path)) {
            $this->logger->debug("[FoF Sitemap] ProxyDisk: Set $setIndex not found in remote storage");

            return null;
        }

        $this->logger->debug("[FoF Sitemap] ProxyDisk: Serving set $setIndex from remote storage");

        return $this->sitemapStorage->get($path);
    }
    public function removeStaleSets(int $setCount): void
    {
        foreach ($this->sitemapStorage->files() as $path) {
            if (preg_match('/^sitemap-(\d+)\.xml$/D', $path, $matches) && (int) $matches[1] >= $setCount) {
                $this->logger->info('[FoF Sitemap] Removing stale set: '.$path);
                if (!$this->sitemapStorage->delete($path)) {
                    throw new \RuntimeException('Unable to delete stale sitemap set: '.$path);
                }
            }
        }
    }
}
