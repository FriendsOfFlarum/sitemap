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

class RemoveResource implements ExtenderInterface
{
    /**
     * Remove a resource from the sitemap. Specify the ::class of the resource.
     * Resource must extend FoF\Sitemap\Resources\Resource.
     *
     * @param string $resource
     */
    public function __construct(
        private string $resource
    ) {
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('fof-sitemaps.resources', function (array $resources) {
            return array_filter($resources, function ($res) {
                return $res !== $this->resource;
            });
        });
    }
}
