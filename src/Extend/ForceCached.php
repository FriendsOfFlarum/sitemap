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

namespace FoF\Sitemap\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Disables the runtime mode and any other mode other extensions might have added.
 * Intended for use in managed hosting.
 *
 * @deprecated Use FoF\Sitemap\Extend\Sitemap::forceCached() instead. Will be removed in Flarum 2.0.
 */
class ForceCached implements ExtenderInterface
{
    private Sitemap $sitemap;

    public function __construct()
    {
        $this->sitemap = (new Sitemap())->forceCached();
    }

    public function extend(Container $container, ?Extension $extension = null)
    {
        $this->sitemap->extend($container, $extension);
    }
}
