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

use Carbon\Carbon;
use Illuminate\View\Factory;

class Url
{
    public function __construct(
        public string $location,
        public ?Carbon $lastModified = null,
        public ?string $changeFrequency = null,
        public ?float $priority = null
    ) {
    }

    public function toXML(Factory $view): string
    {
        return $view->make('fof-sitemap::url')->with('url', $this)->render();
    }
}
