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
use Flarum\Discussion\Discussion;
use Flarum\User\Guest;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Query\Builder;
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
            static::$extensionManager->isEnabled('flarum-tags') && !static::$settings->get('fof-sitemap.excludeTags')
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

    public function lastModifiedAt($routeName): ?Carbon
    {
        if ($routeName !== 'index' || static::$settings->get('default_route') !== '/all') {
            return null;
        }

        // last_posted_at is indexed; fetch only the latest guest-visible activity.
        $query = Discussion::whereVisibleTo(new Guest());

        if (static::$extensionManager->isEnabled('flarum-tags')) {
            // Hidden tags are guest-visible but excluded from All Discussions.
            $query->whereNotExists(function (Builder $tags) {
                $tags->selectRaw('1')
                    ->from('discussion_tag')
                    ->join('tags', 'tags.id', '=', 'discussion_tag.tag_id')
                    ->whereColumn('discussion_tag.discussion_id', 'discussions.id')
                    ->where('tags.is_hidden', true);
            });
        }

        return $query->whereNotNull('discussions.last_posted_at')
            ->orderByDesc('discussions.last_posted_at')
            ->value('discussions.last_posted_at');
    }
}
