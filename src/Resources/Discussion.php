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

use Carbon\Carbon;
use Flarum\Discussion\Discussion as Model;
use Flarum\User\Guest;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Eloquent\Builder;

class Discussion extends Resource
{
    public function query(): Builder
    {
        return Model::whereVisibleTo(new Guest());
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('discussion', [
            'id' => $model->id.(trim($model->slug) ? '-'.$model->slug : ''),
        ]);
    }

    public function priority(): float
    {
        return 0.7;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }

    public function lastModifiedAt($model): Carbon
    {
        return $model->last_posted_at;
    }
}
