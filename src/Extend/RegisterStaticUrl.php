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
 * @deprecated Use FoF\Sitemap\Extend\Sitemap::addStaticUrl() instead. Will be removed in Flarum 2.0.
 */
class RegisterStaticUrl implements ExtenderInterface
{
    private Sitemap $sitemap;

    /**
     * Add a static url to the sitemap. Specify the route name.
     *
     * @param string $routeName
     */
    public function __construct(string $routeName)
    {
        $this->sitemap = (new Sitemap())->addStaticUrl($routeName);
    }

    public function extend(Container $container, ?Extension $extension = null)
    {
        $this->sitemap->extend($container, $extension);
    }
}
