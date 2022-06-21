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

namespace FoF\Sitemap\Sitemap;

use FoF\Sitemap\Exceptions\SetLimitReachedException;
use Illuminate\View\Factory;

class UrlSet
{
    const AMOUNT_LIMIT = 50000;

    /**
     * @var Url[]
     */
    public $urls = [];

    public function add(Url $url)
    {
        if (count($this->urls) >= static::AMOUNT_LIMIT) {
            throw new SetLimitReachedException();
        }

        $this->urls[] = $url;
    }

    public function addUrl($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        $this->add(new Url($location, $lastModified, $changeFrequency, $priority));
    }

    public function toXml(): string
    {
        /** @var Factory $view */
        $view = resolve(Factory::class);

        return $view->make('fof-sitemap::urlset')
            ->with('set', $this)
            ->render();
    }
}
