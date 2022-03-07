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

use Illuminate\View\Factory;

class UrlSet
{
    /**
     * @var Url[]
     */
    public $urls = [];

    public function add(Url $url)
    {
        $this->urls[] = $url;
    }

    public function addUrl($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        $this->add(new Url($location, $lastModified, $changeFrequency, $priority));
    }

    public function toXml(Factory $view): string
    {
        return $view->make('fof-sitemap::urlset')->with('set', $this)->make();
    }
}
