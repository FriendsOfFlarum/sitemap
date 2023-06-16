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

namespace FoF\Sitemap\Resources;

use Carbon\Carbon;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Support\Collection;

class StaticUrls extends Resource
{
    public static array $routes = [
        'index',
    ];

    public static function addRoute(string $routeName)
    {
        static::$routes[] = $routeName;
    }

    public function query(): Collection
    {
        if (
            // If the tags extension is enabled...
            static::$extensionManager->isEnabled('flarum-tags')
            // ...and route is not already added
            && !in_array('tags', static::$routes)
        ) {
            static::addRoute('tags');
        }

        return collect(static::$routes);
    }

    public function url($routeName): string
    {
        return $this->generateRouteUrl($routeName);
    }

    public function priority(): float
    {
        return 0.3;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }

    public function lastModifiedAt($routeName): Carbon
    {
        return Carbon::now();
    }
}
