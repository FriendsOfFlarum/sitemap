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
 */
class ForceCached implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        $container->instance('fof-sitemaps.forceCached', true);
    }
}
