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

use Flarum\User\Guest;
use Flarum\User\User as Model;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Eloquent\Builder;

class User extends Resource
{
    public function query(): Builder
    {
        $query = Model::whereVisibleTo(new Guest())
            ->where('comment_count', '>', static::$settings->get('fof-sitemap.model.user.comments.minimum_item_threshold'));

        if (static::$settings->get('fof-sitemap.riskyPerformanceImprovements')) {
            // This is a risky statement for the same reasons as the Discussion resource
            $query->select([
                'id',
                'username',
            ]);
        }

        return $query;
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('user', [
            'username' => $this->generateModelSlug(Model::class, $model),
        ]);
    }

    public function priority(): float
    {
        return 0.5;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }

    public function enabled(): bool
    {
        return !static::$settings->get('fof-sitemap.excludeUsers');
    }
}
