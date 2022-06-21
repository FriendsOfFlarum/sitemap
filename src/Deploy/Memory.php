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
use Laminas\Diactoros\Uri;

class Memory implements DeployInterface
{
    protected array $cache = [];

    public function __construct(
        public UrlGenerator $urlGenerator
    ) {
    }

    public function storeSet($setIndex, string $set): ?StoredSet
    {
        $this->cache[$setIndex] = $set;

        return new StoredSet(
            $this->urlGenerator->to('forum')->route('fof-sitemap-live', [
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

    public function getIndex(): ?Uri
    {
        return new Uri($this->urlGenerator->to('forum')->route('fof-sitemap-live', [
            'id' => 'index',
        ]));
    }
}
