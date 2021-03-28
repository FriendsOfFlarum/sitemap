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

class Url
{
    public $location;
    /**
     * @var Carbon
     */
    public $lastModified;
    public $changeFrequency;
    public $priority;

    public function __construct($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        $this->location = $location;
        $this->lastModified = $lastModified;
        $this->changeFrequency = $changeFrequency;
        $this->priority = $priority;
    }
}
