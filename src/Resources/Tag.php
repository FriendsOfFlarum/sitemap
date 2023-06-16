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

use Flarum\Tags\Tag as Model;
use Flarum\User\Guest;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Eloquent\Builder;

class Tag extends Resource
{
    public function query(): Builder
    {
        return Model::whereVisibleTo(new Guest())
            ->where('discussion_count', '>', static::$settings->get('fof-sitemap.model.tags.discussion.minimum_item_threshold'));
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('tag', [
            'slug' => $model->slug,
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
        return static::$extensionManager->isEnabled('flarum-tags');
    }
}
