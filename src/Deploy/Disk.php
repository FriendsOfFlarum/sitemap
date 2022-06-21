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
use FoF\Sitemap\Jobs\TriggerBuildJob;
use Illuminate\Contracts\Filesystem\Cloud;
use Laminas\Diactoros\Uri;

class Disk implements DeployInterface
{
    public function __construct(
        public Cloud $sitemapStorage,
        public Cloud $indexStorage
    ) {
    }

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

    public function getIndex(): ?Uri
    {
        if (!$this->indexStorage->exists('sitemap.xml')) {
            // build the index for the first time
            resolve('flarum.queue.connection')->push(new TriggerBuildJob());
        }

        $uri = $this->indexStorage->url('sitemap.xml');

        return $uri
            ? new Uri($uri)
            : null;
    }
}
