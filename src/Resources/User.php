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
        return Model::whereVisibleTo(new Guest());
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
}
