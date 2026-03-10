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
use Psr\Log\LoggerInterface;

class Memory implements DeployInterface
{
    protected array $cache = [];

    public function __construct(
        public UrlGenerator $urlGenerator,
        protected LoggerInterface $logger
    ) {
    }

    public function storeSet(int $setIndex, $stream): ?StoredSet
    {
        // Memory deploy materialises the stream into a string. This is intentional:
        // the Memory backend is only used for small/development forums where the
        // sitemap fits comfortably in RAM. Large production forums must use the
        // Disk or ProxyDisk backend, which pass the stream directly to the filesystem.
        $this->cache[$setIndex] = stream_get_contents($stream);

        return new StoredSet(
            $this->urlGenerator->to('forum')->route('fof-sitemap-set', [
                'id' => $setIndex,
            ]),
            Carbon::now()
        );
    }

    /**
     * Additional method that isn't part of the interface to retrieve in-memory cache
     * This method is also used to retrieve the index which will be cached with string index "index".
     *
     * @param string|int $setIndex
     *
     * @return string|null
     */
    public function getSet($setIndex): ?string
    {
        return $this->cache[$setIndex] ?? null;
    }

    public function storeIndex(string $index): ?string
    {
        $this->cache['index'] = $index;

        return $this->getIndex();
    }

    public function getIndex(): ?string
    {
        $this->logger->debug('[FoF Sitemap] Memory: Serving index from in-memory cache');

        return $this->getSet('index');
    }
}
