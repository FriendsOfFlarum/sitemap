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

        $this->logger->info("[FoF Sitemap] Disk: Storing set $setIndex to path: $path");
        $this->logger->info('[FoF Sitemap] Disk: Full filesystem path: '.$this->sitemapStorage->url($path));

        try {
            $result = $this->sitemapStorage->put($path, $set);
            $this->logger->info("[FoF Sitemap] Disk: Successfully stored set $setIndex, result: ".($result ? 'true' : 'false'));
        } catch (\Exception $e) {
            $this->logger->error("[FoF Sitemap] Disk: Failed to store set $setIndex: ".$e->getMessage());

            throw $e;
        }

        return new StoredSet(
            $this->url->to('forum')->route('fof-sitemap-set', ['id' => $setIndex]),
            Carbon::now()
        );
    }

    public function storeIndex(string $index): ?string
    {
        $this->logger->info('[FoF Sitemap] Disk: Storing index to sitemap.xml');

        try {
            $result = $this->indexStorage->put('sitemap.xml', $index);
            $this->logger->info('[FoF Sitemap] Disk: Successfully stored index, result: '.($result ? 'true' : 'false'));
        } catch (\Exception $e) {
            $this->logger->error('[FoF Sitemap] Disk: Failed to store index: '.$e->getMessage());

            throw $e;
        }

        return $this->url->to('forum')->route('fof-sitemap-index');
    }

    public function getIndex(): ?string
    {
        $fullPath = $this->indexStorage->url('sitemap.xml');
        $this->logger->debug("[FoF Sitemap] Disk: Checking for index at: {$fullPath}");

        if (!$this->indexStorage->exists('sitemap.xml')) {
            $this->logger->debug('[FoF Sitemap] Disk: Index not found, triggering build job');
            resolve('flarum.queue.connection')->push(new TriggerBuildJob());

            return null;
        }

        $this->logger->debug("[FoF Sitemap] Disk: Serving index from: {$fullPath}");

        return $this->indexStorage->get('sitemap.xml');
    }

    public function getSet($setIndex): ?string
    {
        $path = "sitemap-$setIndex.xml";
        $fullPath = $this->sitemapStorage->url($path);

        $this->logger->debug("[FoF Sitemap] Disk: Checking for set $setIndex at: {$fullPath}");

        if (!$this->sitemapStorage->exists($path)) {
            $this->logger->debug("[FoF Sitemap] Disk: Set $setIndex not found in local storage");

            return null;
        }

        $this->logger->debug("[FoF Sitemap] Disk: Serving set $setIndex from: {$fullPath}");

        return $this->sitemapStorage->get($path);
    }
}
