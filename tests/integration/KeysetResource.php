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

namespace FoF\Sitemap\Tests\integration;

use Flarum\Discussion\Discussion;
use FoF\Sitemap\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class KeysetResource extends Resource
{
    public function query(): Builder
    {
        return Discussion::query();
    }

    public function url($model): string
    {
        return 'https://example.com/d/'.substr($model->slug, 11);
    }

    public function priority(): float
    {
        return 0.5;
    }

    public function frequency(): string
    {
        return 'daily';
    }
}
