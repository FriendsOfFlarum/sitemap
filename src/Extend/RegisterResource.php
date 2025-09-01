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
 * @deprecated Use FoF\Sitemap\Extend\Sitemap::addResource() instead. Will be removed in Flarum 2.0.
 */
class RegisterResource implements ExtenderInterface
{
    private Sitemap $sitemap;

    /**
     * Add a resource from the sitemap. Specify the ::class of the resource.
     * Resource must extend FoF\Sitemap\Resources\Resource.
     *
     * @param string $resource
     */
    public function __construct(string $resource)
    {
        $this->sitemap = (new Sitemap())->addResource($resource);
    }

    public function extend(Container $container, ?Extension $extension = null)
    {
        $this->sitemap->extend($container, $extension);
    }
}
