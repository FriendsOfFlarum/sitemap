<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE.md
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
        return Model::whereVisibleTo(new Guest());
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('tag', [
            'slug' => $model->slug,
        ]);
    }

    public function priority(): float
    {
        return 0.9;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }
}
