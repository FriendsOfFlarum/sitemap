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
use FoF\Sitemap\Resources\Resource;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class RegisterResource implements ExtenderInterface
{
    /**
     * @var string
     */
    private $resource;

    /**
     * Add a resource from the sitemap. Specify the ::class of the resource.
     * Resource must extend FoF\Sitemap\Resources\Resource.
     *
     * @param string $resource
     */
    public function __construct(string $resource)
    {
        $this->resource = $resource;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('fof.sitemap.resources', function (array $resources) use ($container) {
            $resource = $container->make($this->resource);

            if ($resource instanceof Resource) {
                $resources[] = $resource;
            } else {
                throw new InvalidArgumentException("{$this->resource} has to extend ".Resource::class);
            }

            return $resources;
        });
    }
}
