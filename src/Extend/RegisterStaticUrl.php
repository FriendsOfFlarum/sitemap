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
use FoF\Sitemap\Resources\StaticUrls;
use Illuminate\Contracts\Container\Container;

class RegisterStaticUrl implements ExtenderInterface
{
    /**
     * Add a static url to the sitemap. Specify the route name.
     *
     * @param string $routeName
     */
    public function __construct(
        private string $routeName
    ) {
    }

    public function extend(Container $container, Extension $extension = null)
    {
        StaticUrls::addRoute($this->routeName);
    }
}
